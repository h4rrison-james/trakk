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
        NSString *university = @"UWA";
        NSString *path = [NSString stringWithFormat:@"Tiles/%@", university];
        DLog(@"Overlay Path: %@", path);
        NSString *tileDirectory = [[[NSBundle mainBundle] resourcePath] stringByAppendingPathComponent:path];
        TileOverlay *overlay = [[TileOverlay alloc] initWithTileDirectory:tileDirectory];
        [mapView addOverlay:overlay];
    }
    
    //Enable clustering by group
    mapView.clusterByGroupTag = TRUE;
    
    //Add pin annotations
    [self addAnnotations];
    
    [super viewDidLoad];
}

-(void)addAnnotations
{ //Adds all marker annotations to the map
    
    //Zoom map to standard view
    [self zoomToFitUniversity:mapView];
    
    //Remove all current annotations before adding new ones
    [self.mapView removeAnnotations:[self.mapView annotations]];
    
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
                
                //Set image if it exists
                if ([self exists:user withKey:@"picture"]) {
                    PFFile *picture = [user objectForKey:@"picture"];
                    NSData *data = [picture getData];
                    pin.image = [UIImage imageWithData:data];
                }
                else {
                    pin.image = [UIImage imageNamed:@"Default-Avatar"];
                }
                
                //Set group tag
                pin.groupTag = @"person";
                
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
            pin.groupTag = @"club"; //Set group tag
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
    //If annotation is not the default current location annotation
    if(![[annotation title] isEqualToString:@"Current Location"])
    { 
        //Set up the custom marker
        MKPinAnnotationView *markerView = nil;
        
        //Requeue annotation view
        static NSString *defaultID = @"com.invasivecode.pin";
        markerView = (MKPinAnnotationView *)[mapView dequeueReusableAnnotationViewWithIdentifier:defaultID];
        if ( markerView == nil )
            markerView = [[MKPinAnnotationView alloc] initWithAnnotation:annotation reuseIdentifier:defaultID];
        
        //Set default options
        markerView.canShowCallout = YES;
        markerView.animatesDrop = YES;
    
        //If annotation is a cluster type  
        if ([annotation isKindOfClass:[OCAnnotation class]])
        {  
            OCAnnotation *clusterAnnotation = (OCAnnotation *)annotation;
            int numberPeople = [clusterAnnotation.annotationsInCluster count];
            
            //Set title depending on how many people there are in the cluster
            if (numberPeople < 3)
            { //Display lengthier title
                NSString *title = [[NSString alloc] init];
                for (userAnnotation* annotation in clusterAnnotation.annotationsInCluster)
                {
                    NSArray *nameArray = [annotation.title componentsSeparatedByString:@" "];
                    title = [title stringByAppendingString:[nameArray objectAtIndex:0]];
                    title = [title stringByAppendingString:@" "];
                    title = [title stringByAppendingString:[[nameArray lastObject] substringToIndex:1]];
                    title = [title stringByAppendingString:@" & "];
                }
                title = [title substringToIndex:(title.length - 3)];
                clusterAnnotation.title = title;
            }
            else
            { //Display shortened title
                NSString *title = [[NSString alloc] init];
                userAnnotation *annotation = [clusterAnnotation.annotationsInCluster objectAtIndex:0];
                NSArray *nameArray = [annotation.title componentsSeparatedByString:@" "];
                title = [title stringByAppendingString:[nameArray objectAtIndex:0]];
                title = [title stringByAppendingString:@" "];
                title = [title stringByAppendingString:[[nameArray lastObject] substringToIndex:1]];
                title = [NSString stringWithFormat:@"%@. and %d others", title, numberPeople-1];
                clusterAnnotation.title = title;
            }
            
            //Set subtitle
            NSString *sub = [[clusterAnnotation.annotationsInCluster objectAtIndex:0] subtitle];
            NSArray *subArray = [sub componentsSeparatedByString:@" @ "];
            clusterAnnotation.subtitle = [subArray lastObject];
            
            
            //Configure cluster annotation specifics
            markerView.pinColor = MKPinAnnotationColorGreen;
            markerView.rightCalloutAccessoryView = [UIButton buttonWithType:UIButtonTypeDetailDisclosure];
            
            //Set profile image in callout
            UIImageView *image = [[UIImageView alloc] initWithImage:[UIImage imageNamed:@"Default-Avatar"]];
            image.contentMode = UIViewContentModeScaleAspectFill;
            image.clipsToBounds = YES;
            image.frame = CGRectMake(0, 0, 30, 30);
            markerView.leftCalloutAccessoryView = image;
            
            return markerView;
        }  
        //If regular annotation  
        else if([annotation isKindOfClass:[userAnnotation class]])
        { 
            //Configure single annotation specifics
            markerView.pinColor = [annotation pinColor];
            
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
      
    }  
    
    return nil;
}

- (void)mapView:(MKMapView *)mapView annotationView:(MKAnnotationView *)view calloutAccessoryControlTapped:(UIControl *)control
{
    //If regular annotation selected, then push chat controller
    if ([view.annotation isKindOfClass:[userAnnotation class]])
    {
        DetailViewController *detail = [[DetailViewController alloc] init];
        [detail setHidesBottomBarWhenPushed:YES];
        userAnnotation *annotation = (userAnnotation *)view.annotation;
        detail.title = annotation.title;
        detail.userID = [annotation.user objectId];
        [self.navigationController pushViewController:detail animated:YES];
    }
    //Otherwise if annotation is a cluster, push table of annotations
    else if ([view.annotation isKindOfClass:[OCAnnotation class]])
    {
        OCAnnotation *annotation = (OCAnnotation*)view.annotation;
        annotationArray = annotation.annotationsInCluster;
        [self performSegueWithIdentifier:@"profile" sender:self];
    }
}

-(void)prepareForSegue:(UIStoryboardSegue *)segue sender:(id)sender
{
    if ([segue.identifier isEqualToString:@"profile"])
    { //Check segue is to cluster view controller
        ClusterViewController *cluster = [segue destinationViewController];
        cluster.annotations = annotationArray;
        NSString *sub = [[annotationArray objectAtIndex:0] subtitle];
        NSArray *subArray = [sub componentsSeparatedByString:@" @ "];
        cluster.title = [subArray lastObject];
    }
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
