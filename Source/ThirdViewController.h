//
//  ThirdViewController.h
//  Trakk
//
//  Created by Harrison Sweeney on 3/07/11.
//  Copyright 2011 Harrison J Sweeney. All rights reserved.
//

//Import Frameworks
#import <UIKit/UIKit.h>
#import <MapKit/MapKit.h>

//Import Custom Headers
#import "utrakAppDelegate.h"
#import "DataController.h"
#import "OCMapView.h"
#import "ClusterViewController.h"

@interface ThirdViewController : UIViewController
<MKMapViewDelegate> {
    NSArray *friendArray;
    NSArray *poiArray;
    OCMapView *mapView;
    NSArray *annotationArray;
}

-(void)zoomToFitUniversity:(MKMapView*)map;
-(void)addAnnotations;
@property (nonatomic, strong) NSArray *friendArray;
@property (nonatomic, strong) NSArray *poiArray;
@property (nonatomic, strong) IBOutlet MKMapView *mapView;

@end
