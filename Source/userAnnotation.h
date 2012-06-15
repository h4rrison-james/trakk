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

@interface userAnnotation : NSObject <MKAnnotation> {
    CLLocationCoordinate2D coordinate;
    MKPinAnnotationColor pinColor;
    NSString *title;
    NSString *subtitle;
    UIImage *image;
    PFUser *user;
}

@property (nonatomic, assign) CLLocationCoordinate2D coordinate;
@property (nonatomic, assign) MKPinAnnotationColor pinColor;
@property (nonatomic, copy) NSString *title;
@property (nonatomic, copy) NSString *subtitle;
@property (nonatomic, copy) UIImage *image;
@property (nonatomic, strong) PFUser *user;

@end
