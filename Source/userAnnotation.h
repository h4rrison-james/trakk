//
//  userAnnotation.h
//  Trakk
//
//  Created by Harrison Sweeney on 22/07/11.
//  Copyright 2011 Harrison J Sweeney. All rights reserved.
//

#import <Foundation/Foundation.h>
#import <MapKit/MapKit.h>
#import "Parse/Parse.h"
#import "OCGrouping.h"

@interface userAnnotation : NSObject <MKAnnotation, OCGrouping> {
    CLLocationCoordinate2D coordinate;
    MKPinAnnotationColor pinColor;
    NSString *title;
    NSString *subtitle;
    NSString *_groupTag;
    UIImage *image;
    PFUser *user;
}

@property (nonatomic, assign) CLLocationCoordinate2D coordinate;
@property (nonatomic, assign) MKPinAnnotationColor pinColor;
@property (nonatomic, copy) NSString *title;
@property (nonatomic, copy) NSString *subtitle;
@property (nonatomic, copy) NSString *groupTag;
@property (nonatomic, copy) UIImage *image;
@property (nonatomic, strong) PFUser *user;

@end
