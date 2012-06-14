//
//  LocationController.m
//  Trakk
//
//  Created by Harrison Sweeney on 2/07/11.
//  Copyright 2011 UWA. All rights reserved.
//

#import "Constants.h"
#import "LocationController.h"
#import <SystemConfiguration/CaptiveNetwork.h>

static LocationController *sharedClient;

@implementation LocationController

@synthesize currentLocation;
@synthesize isUpdating;
@synthesize previousDelegate;

+ (LocationController *)sharedClient {
    @synchronized(self) {
        if (!sharedClient)
            sharedClient=[[LocationController alloc] init];      
    }
    return sharedClient;
}

+(id)alloc {
    @synchronized(self) {
        NSAssert(sharedClient == nil, @"Cannot create second LocationController");
        sharedClient = [super alloc];
    }
    return sharedClient;
}

-(id) init {
    if (self = [super init]) {
        locationManager = [[CLLocationManager alloc] init];
        locationManager.delegate = self;
        [self start];
    }
    return self;
}

-(void) start {
    if ([PFUser currentUser] && ![[[PFUser currentUser] objectForKey:@"status"] isEqualToString:@"Offline"])
    { //Only proceed if user logged in and not offline
        //Toggle location updates, then attempt to find location with BSSID
        [locationManager startUpdatingLocation];
        [locationManager stopUpdatingLocation];
        
        NSDictionary *info = [self fetchSSIDInfo];
        if (info != NULL)
        { //If wifi is on and connected to something
            NSString *BSSID = [info objectForKey:@"BSSID"];
            PFQuery *query = [PFQuery queryWithClassName:@"WAP"];
            [query whereKey:@"BSSID" equalTo:BSSID];
            [query includeKey:@"Location"];
            [query findObjectsInBackgroundWithBlock:^(NSArray *objects, NSError *error) {
                if (!error && [objects count] == 1)
                { //Specific match for BSSID found
                    DLog(@"Match for BSSID found. Updating Location");
                    PFObject *WAP = [objects objectAtIndex:0];
                    PFObject *location = [WAP objectForKey:@"Location"];
                    //Update user location and coordinates
                    [[PFUser currentUser] setObject:location forKey:@"location"];
                    PFGeoPoint *geoPoint = [location objectForKey:@"location"];
                    [[PFUser currentUser] setObject:geoPoint forKey:@"coordinates"];
                    [[PFUser currentUser] saveEventually];
                    //Schedule location update
                    NSUserDefaults *defaults = [NSUserDefaults standardUserDefaults];
                    NSTimeInterval interval;
                    if ([defaults objectForKey:@"updateInterval"])
                        interval = [[defaults objectForKey:@"updateInterval"] doubleValue];
                    else 
                        interval = UPDATE_INTERVAL;
                    [[LocationController sharedClient] performSelector:@selector(start) withObject:nil afterDelay:interval];
                }
                else
                { //No specific match found, proceed with regular location updates
                    DLog(@"BSSID match not found. Updating normally.");
                    [locationManager startUpdatingLocation];
                    [NSTimer timerWithTimeInterval:30 target:self selector:@selector(stop) userInfo:nil repeats:NO];
                }
            }];
        }
        else
        { //Wifi is either off or disconnected, start updating normally.
            DLog(@"Wifi not available. Updating Normally.");
            [locationManager startUpdatingLocation];
            [NSTimer timerWithTimeInterval:30 target:self selector:@selector(stop) userInfo:nil repeats:NO];
        }
    }
    else
    { //Flag error for trying to update while user not logged in
        [locationManager stopUpdatingLocation];
        DLog(@"User not logged in, not attempting to update");
    }
}

-(void) stop {
    [locationManager stopUpdatingLocation];
}

-(void) updateDelegate:(id)delegate {
    if (delegate)
    {
        locationManager.delegate = delegate;
        previousDelegate = delegate;
    }
    else locationManager.delegate = self;
}

- (void) setIsUpdating:(BOOL)boolean
{
    isUpdating = boolean;
}

- (void)locationManager:(CLLocationManager *)manager didUpdateToLocation:(CLLocation *)newLocation fromLocation:(CLLocation *)oldLocation
{
    //Ignore if more than 120 seconds old
    if (abs([newLocation.timestamp timeIntervalSinceDate: [NSDate date]]) < 120) {
        if (newLocation.horizontalAccuracy < 50)
        { //Assume location is accurate enough and turn off location updates for time interval
            DLog(@"Location Accurate");
            NSUserDefaults *defaults = [NSUserDefaults standardUserDefaults];
            NSTimeInterval interval;
            if ([defaults objectForKey:@"updateInterval"]) {
                interval = [[defaults objectForKey:@"updateInterval"] doubleValue];
            }
            else {
                //Default interval value 
                interval = UPDATE_INTERVAL;
            }
            
            [[LocationController sharedClient] stop];
            [[LocationController sharedClient] performSelector:@selector(start) withObject:nil afterDelay:interval];
            
            //Find the nearest saved location
            double latitude = newLocation.coordinate.latitude;
            double longitude = newLocation.coordinate.longitude;
            PFGeoPoint *currentGeoPoint = [PFGeoPoint geoPointWithLatitude:latitude longitude:longitude];
            PFQuery *query = [PFQuery queryWithClassName:@"Location"];
            [query whereKey:@"location" nearGeoPoint:currentGeoPoint];
            query.limit = 1;
            [self setCurrentLocation:newLocation];
            [query findObjectsInBackgroundWithTarget:self selector:@selector(locationCallback:error:)];
        }
    }
}

- (void)locationCallback:(NSArray *)results error:(NSError *)error
{
    PFObject *callbackLocationObject = [results objectAtIndex:0];
    DLog(@"Closest point is %@", [callbackLocationObject objectForKey:@"name"]);
    PFGeoPoint *currentGeoPoint = [callbackLocationObject objectForKey:@"location"];
    CLLocation *stored = [[CLLocation alloc] initWithLatitude:[currentGeoPoint latitude] longitude:[currentGeoPoint longitude]];
    double radius = [[callbackLocationObject objectForKey:@"radius"] doubleValue];
    CLLocationDegrees lat = currentLocation.coordinate.latitude;
    CLLocationDegrees lon = currentLocation.coordinate.longitude;
    PFGeoPoint *actualGeoPoint = [PFGeoPoint geoPointWithLatitude:lat longitude:lon];
    DLog(@"Current Location: (%f, %f)", lat, lon);
    DLog(@"Stored Location: (%f, %f)", stored.coordinate.latitude, stored.coordinate.longitude);
    double distance = [currentLocation distanceFromLocation:stored];
    if (distance < radius)
    { //Location is within trigger radius
        PFUser *user = [PFUser currentUser];
        NSString *currentName = [callbackLocationObject objectForKey:@"name"];
        DLog(@"User location within trigger radius of %@", currentName);
        if ([user objectForKey:@"location"]) 
        { //Check if location key exists in current user
            //NSString *storedName = [[user objectForKey:@"location"] objectForKey:@"name"];
            [user setObject:callbackLocationObject forKey:@"location"]; //Set callback location
            [user setObject:actualGeoPoint forKey:@"coordinates"]; //Set actual co-ordinates
            [user saveEventually];
            DLog(@"User Location Updated");
        }
        else
        { //If no location exists, upload current location
            [[PFUser currentUser] setObject:callbackLocationObject forKey:@"location"];
            [[PFUser currentUser] setObject:actualGeoPoint forKey:@"coordinates"];
            [[PFUser currentUser] saveEventually];
            DLog(@"User Location Updated for first time.");
        }
    }
    else
    { //Location is not within trigger radius, upload null location
        NSNull *null = [NSNull null];
        [[PFUser currentUser] setObject:null forKey:@"location"];
        [[PFUser currentUser] setObject:actualGeoPoint forKey:@"coordinates"];
        [[PFUser currentUser] saveEventually];
        DLog(@"User outside trigger radius, clearing location.");
    }
}

- (void)locationManager:(CLLocationManager *)manager didFailWithError:(NSError *)error {
    DLog(@"Background: Location failed with error (%@)", error);
}

#pragma mark MAC Adress Functions

- (NSDictionary *)fetchSSIDInfo
{
    NSArray *ifs = (__bridge_transfer id)CNCopySupportedInterfaces();
    NSDictionary* info = nil;
    for (NSString *ifnam in ifs)
    {
        info = (__bridge_transfer id)CNCopyCurrentNetworkInfo((__bridge_retained CFStringRef)ifnam);
    }
    return info;
}


@end
