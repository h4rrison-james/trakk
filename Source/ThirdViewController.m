//
//  ThirdViewController.m
//  utrak
//
//  Created by Harrison Sweeney on 3/07/11.
//  Copyright 2011 UWA. All rights reserved.
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
{ //Adds all annotations to the map
    [self zoomToFitUniversity:mapView];
    
    //Add friend pins to the map
    if ([friendArray count])
    { //Check that friendArray exists
        for (PFUser *user in friendArray)
        { //For each user object
            BOOL online = ![[user objectForKey:@"status"] isEqualToString:@"Offline"];
            BOOL coordinateExists = [user objectForKey:@"coordinates"] && ![[user objectForKey:@"coordinates"] isKindOfClass:[NSNull class]];
            if (online && coordinateExists)
            { //Check that user is online
                
                //Add annotation for user to the map
                userAnnotation *pin = [[userAnnotation alloc] init];
                
                //Set color
                pin.pinColor = MKPinAnnotationColorPurple;
                
                //Set user
                pin.user = user;
                BOOL nameExists = [user objectForKey:@"name"] && ![[user objectForKey:@"name"] isKindOfClass:[NSNull class]];
                BOOL locationExists = [user objectForKey:@"location"] && ![[user objectForKey:@"location"] isKindOfClass:[NSNull class]];
                BOOL statusExists = [user objectForKey:@"status"] && ![[user objectForKey:@"status"] isKindOfClass:[NSNull class]];
                
                //Set name
                if (nameExists)
                { //Check name exists and set name
                    NSString *name = [NSString stringWithFormat:@"%@ %@", [user objectForKey:@"first_name"], [user objectForKey:@"last_name"]];
                    DLog(@"Making pin for user %@", name);
                    pin.title = name;
                }
                
                //Set status text
                NSString *status;
                NSString *statusText;
                if (statusExists)
                    status = [user objectForKey:@"status"];
                else
                    DLog(@"Error: No status is set for user.");
                if (locationExists && statusExists)
                { //If location is not null and status is not null or offline, display full string
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
//    if ([poiArray count])
//    { //Check that POI array exists
//        for (PFObject *object in poiArray)
//        { //For each POI object
//            userAnnotation *pin = [[userAnnotation alloc] init];
//            pin.title = [object objectForKey:@"name"]; //Set title
//            pin.subtitle = [object objectForKey:@"subtitle"]; //Set subtitle
//            PFFile *picture = [object objectForKey:@"image"]; //Set picture
//            NSData *data = [picture getData];
//            pin.image = [UIImage imageWithData:data];
//            PFGeoPoint *loc = [object objectForKey:@"location"]; //Set location
//            CLLocationCoordinate2D coord = CLLocationCoordinate2DMake([loc latitude], [loc longitude]);
//            pin.coordinate = coord;
//            pin.pinColor = MKPinAnnotationColorGreen; //Set color
//            [mapView addAnnotation:pin];
//        }
//    }
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
        MKAnnotationView *markerView = nil;
        
        //Reque annotation view
        static NSString *defaultID = @"com.invasivecode.pin";
        markerView = (MKAnnotationView *)[mapView dequeueReusableAnnotationViewWithIdentifier:defaultID];
        if ( markerView == nil )
            markerView = [[MKAnnotationView alloc] initWithAnnotation:annotation reuseIdentifier:defaultID];
        
        //Configure annotation
        markerView.canShowCallout = YES;
        UIImage *marker = [UIImage imageNamed:@"Pin-Red.png"];
        markerView.contentMode = UIViewContentModeScaleAspectFit;
        markerView.frame = CGRectMake(0, 0, 32, 39);
        markerView.image = marker;
        
        //Set detail disclosure in callout if required
        if ([annotation pinColor] == MKPinAnnotationColorPurple)
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

- (BOOL)shouldAutorotateToInterfaceOrientation:(UIInterfaceOrientation)interfaceOrientation
{
    // Return YES for supported orientations
    return (interfaceOrientation == UIInterfaceOrientationPortrait);
}

- (IBAction)curlMap:(id)sender {
    UIViewController *view = [[UIViewController alloc] init];
    view.modalPresentationStyle = UIModalPresentationCurrentContext;
    view.modalTransitionStyle = UIModalTransitionStylePartialCurl;
    [self.navigationController presentModalViewController:view animated:YES];
}
@end
