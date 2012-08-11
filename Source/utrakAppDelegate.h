//
//  trakkAppDelegate.h
//  Trakk
//
//  Created by Harrison Sweeney on 24/06/11.
//  Copyright 2011 Harrison J Sweeney. All rights reserved.
//

#import <UIKit/UIKit.h>

//Import Custom Headers
#import "LocationController.h" //Location Singleton
#import "DataController.h" //Data singleton
#import "Parse/Parse.h" //Parse Framework
#import "DetailViewController.h"

@interface utrakAppDelegate : UIResponder <UIApplicationDelegate> {
    UIWindow *window;
    NSArray *permissions;
}

@property (strong, nonatomic) UIWindow *window;
@property (strong, nonatomic) NSArray *permissions;
@property (nonatomic) BOOL startedFromNotification;
@property (strong, nonatomic) NSDictionary *notification;

@end
