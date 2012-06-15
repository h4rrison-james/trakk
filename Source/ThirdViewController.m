//
//  ThirdViewController.m
//  Trakk
//
//  Created by Harrison Sweeney on 3/07/11.
//  Copyright 2011 Harrison J Sweeney. All rights reserved.
//

#import "ThirdViewController.h"
#import "DetailViewController.h"
#import "userAnnotation.h"
#import "TileOverlay.h"
#import "TileOverlayView.h"
#import "Constants.h"

@implementation ThirdViewController

@synthesize friendArray;
@synthesize poiArray;
@synthesize mapView;

- (void)didReceiveMemoryWarning
{
    [super didReceiveMemoryWarning];
    // Release any cached data, images, etc that aren't in use.
}

#pragma mark - View lifecycle

- (void)viewDidLoad
{
    //Add shadow to navigation bar
    SET_SHADOW
    
    //Register for refresh notifications
    [[NSNotificationCenter defaultCenter] addObserver:self selector:@selector(addAnnotations) name:@"refreshNotification" object:nil];
    
    //Load friendArray from application delegate if possible
    utrakAppDelegate *delegate = [[UIApplication sharedApplication] delegate];
    if ([delegate friends])
        friendArray = [delegate friends];
    
    //Load poiArray from application delegate if possible
    if ([delegate poiArray])
        poiArray = [delegate poiArray];
    
    //Create an overlay using tiles from the bundle
    if ([PFUser currentUser])
    {
        NSString *university;
        if ([[PFUser currentUser] objectForKey:@"university"]) {
            university = [[PFUser currentUser] objectForKey:@"university"];
        }
        else
        { //Set default university as UWA for now to avoid crashing here
            university = @"UWA";
        }
        NSString *path = [NSString stringWithFormat:@"Tiles/%@", university];
        DLog(@"Overlay Path: %@", path);
        NSString *tileDirectory = [[[NSBundle mainBundle] resourcePath] stringByAppendingPathComponent:path];
        TileOverlay *overlay = [[TileOverlay alloc] initWithTileDirectory:tileDirectory];
        [mapView addOverlay:overlay];
    }
    
    //Add pin annotations
    [self addAnnotations];
    
    [super viewDidLoad];
}

-(void)addAnnotations
{ //Adds all marker annotations to the map
    [self zoomToFitUniversity:mapView];
    NSUserDefaults *defaults = [NSUserDefaults standardUserDefaults];
    
    //Add friend pins to the map
    if ([friendArray count] && [defaults boolForKey:@"showPeopleOnMap"])
    { //Check that friendArray exists
        for (PFUser *user in friendArray)
        { //For each user object
            BOOL online = ![[user objectForKey:@"status"] isEqualToString:@"Offline"];
            if (online && [self exists:user withKey:@"coordinates"])
            { //Check that user is online
                
                //Add annotation for user to the map
                userAnnotation *pin = [[userAnnotation alloc] init];
                
                //Set color
                pin.pinColor = MKPinAnnotationColorGreen;
                
                //Set user
                pin.user = user;
                
                //Set name
                if ([self exists:user withKey:@"name"])
                { //Check name exists and set name
                    NSString *name = [NSString stringWithFormat:@"%@ %@", [user objectForKey:@"first_name"], [user objectForKey:@"last_name"]];
                    DLog(@"Making pin for user %@", name);
                    pin.title = name;
                }
                
                //Set status text
                NSString *status;
                NSString *statusText;
                if ([self exists:user withKey:@"status"])
                    status = [user objectForKey:@"status"];
                else
                    DLog(@"Error: No status is set for user.");
                if ([self exists:user withKey:@"location"] && [self exists:user withKey:@"status"])
                { //If location and status both exist, then display full string
                    NSString *location = [[user objectForKey:@"location"] objectForKey:@"name"];
                    statusText = [NSString stringWithFormat:@"%@ @ %@", status, location];
                }
                else statusText = status;
                pin.subtitle = statusText;
                
                //Set image
                PFFile *picture = [user objectForKey:@"picture"];
                NSData *data = [picture getData];
                pin.image = [UIImage imageWithData:data];
                
                PFGeoPoint *loc = [user objectForKey:@"coordinates"]; //Place pin at actual co-ordinates
                CLLocationCoordinate2D coord = CLLocationCoordinate2DMake([loc latitude], [loc longitude]);
                pin.coordinate = coord;
                [mapView addAnnotation:pin];
            }
        }
    }
    
    //Add POI pins to the map
    if ([poiArray count] && [defaults boolForKey:@"showClubsOnMap"])
    { //Check that POI array exists
        for (PFObject *object in poiArray)
        { //For each POI object
            userAnnotation *pin = [[userAnnotation alloc] init];
            pin.title = [object objectForKey:@"name"]; //Set title
            pin.subtitle = [object objectForKey:@"subtitle"]; //Set subtitle
            PFFile *picture = [object objectForKey:@"image"]; //Set picture
            NSData *data = [picture getData];
            pin.image = [UIImage imageWithData:data];
            PFGeoPoint *loc = [object objectForKey:@"location"]; //Set location
            CLLocationCoordinate2D coord = CLLocationCoordinate2DMake([loc latitude], [loc longitude]);
            pin.coordinate = coord;
            pin.pinColor = MKPinAnnotationColorRed; //Set color
            [mapView addAnnotation:pin];
        }
    }
}

-(void)zoomToFitUniversity:(MKMapView *)map
{
    //TODO: Find zoom co-ordinates for specific university
    MKCoordinateRegion region;
    region.center.latitude = -31.980378;
    region.center.longitude = 115.818129;
    region.span.latitudeDelta = 0.01;
    region.span.longitudeDelta = 0.01;
    region = [map regionThatFits:region];
    [map setRegion:region   animated:YES];
}

- (MKOverlayView *)mapView:(MKMapView *)mapView viewForOverlay:(id<MKOverlay>)overlay
{
    TileOverlayView *view = [[TileOverlayView alloc] initWithOverlay:overlay];
    view.tileAlpha = 0.6;
    return view;
}

- (MKAnnotationView *)mapView:(MKMapView *)mV viewForAnnotation:(id)annotation
{
    if(![[annotation title] isEqualToString:@"Current Location"])
    { 
        //Set up the custom marker
        MKPinAnnotationView *markerView = nil;
        
        //Requeue annotation view
        static NSString *defaultID = @"com.invasivecode.pin";
        markerView = (MKPinAnnotationView *)[mapView dequeueReusableAnnotationViewWithIdentifier:defaultID];
        if ( markerView == nil )
            markerView = [[MKPinAnnotationView alloc] initWithAnnotation:annotation reuseIdentifier:defaultID];
        
        //Configure annotation
        markerView.canShowCallout = YES;
        markerView.pinColor = [annotation pinColor];
        markerView.animatesDrop = YES;
        
        //Set detail disclosure in callout if required
        if ([annotation pinColor] == MKPinAnnotationColorGreen)
            markerView.rightCalloutAccessoryView = [UIButton buttonWithType:UIButtonTypeDetailDisclosure];
        else
            markerView.rightCalloutAccessoryView = nil;
        
        //Set profile image in callout
        UIImageView *image = [[UIImageView alloc] initWithImage:[annotation image]];
        image.contentMode = UIViewContentModeScaleAspectFill; //Set scaling mode
        image.clipsToBounds = YES;
        image.frame = CGRectMake(0, 0, 30, 30); //Resize image to fit annotation
        markerView.leftCalloutAccessoryView = image;
        
        return markerView;
    }
    
    return nil;
}

- (void)mapView:(MKMapView *)mapView annotationView:(MKAnnotationView *)view calloutAccessoryControlTapped:(UIControl *)control
{
    DetailViewController *detail = [[DetailViewController alloc] init];
    [detail setHidesBottomBarWhenPushed:YES];
    userAnnotation *annotation = view.annotation;
    detail.title = annotation.title;
    detail.userID = [annotation.user objectId];
    [self.navigationController pushViewController:detail animated:YES];
}

-(BOOL)exists:(PFUser *)user withKey:(NSString *)key
{ //Helper method for error checking on the PFUser class
    return ([user objectForKey:key] && ![[user objectForKey:key] isKindOfClass:[NSNull class]]);
}

-(void)viewWillAppear:(BOOL)animated
{
    mapView.showsUserLocation = TRUE;
}

-(void)viewDidDisappear:(BOOL)animated
{
    mapView.showsUserLocation = FALSE;
}

- (void)viewDidUnload
{
    [self setMapView:nil];
    [super viewDidUnload];
    // Release any retained subviews of the main view.
    // e.g. self.myOutlet = nil;
}


@end
