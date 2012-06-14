//
//  ThirdViewController.h
//  utrak
//
//  Created by Harrison Sweeney on 3/07/11.
//  Copyright 2011 UWA. All rights reserved.
//

//Import Frameworks
#import <UIKit/UIKit.h>
#import <MapKit/MapKit.h>

//Import Custom Headers
#import "utrakAppDelegate.h"

@interface ThirdViewController : UIViewController
<MKMapViewDelegate> {
    NSArray *friendArray;
    NSArray *poiArray;
    MKMapView *mapView;
}

-(void)zoomToFitUniversity:(MKMapView*)map;
-(void)addAnnotations;
@property (nonatomic, strong) NSArray *friendArray;
@property (nonatomic, strong) NSArray *poiArray;
@property (nonatomic, strong) IBOutlet MKMapView *mapView;
- (IBAction)curlMap:(id)sender;

@end
