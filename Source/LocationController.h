//
//  LocationController.h
//  Trakk
//
//  Created by Harrison Sweeney on 2/07/11.
//  Copyright 2011 UWA. All rights reserved.
//

#import <Foundation/Foundation.h>
#import <CoreLocation/CoreLocation.h>
#import "Parse/Parse.h"

@interface LocationController : NSObject <CLLocationManagerDelegate>
{
    CLLocationManager *locationManager;
    CLLocation *currentLocation;
    BOOL isUpdating;
    id previousDelegate;
}

@property (nonatomic, strong) CLLocation *currentLocation;
@property (nonatomic) BOOL isUpdating;
@property (nonatomic, strong) id previousDelegate;

+ (LocationController *)sharedClient;
- (void) updateDelegate:(id)delegate;
- (void) setIsUpdating:(BOOL)isUpdating;
- (void)locationCallback:(NSArray *)results error:(NSError *)error;
- (NSDictionary *)fetchSSIDInfo;
- (void) start;
- (void) stop;

@end
