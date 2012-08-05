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
#import "Parse/Parse.h" //Parse Framework
#import "DetailViewController.h"

@interface utrakAppDelegate : UIResponder <UIApplicationDelegate> {
    UIWindow *window;
    NSArray *permissions;
    NSMutableArray *friends;
    NSMutableArray *facebookFriends;
    NSArray *poiArray;
}

@property (strong, nonatomic) UIWindow *window;
@property (strong, nonatomic) NSArray *permissions;
@property (strong, nonatomic) NSMutableArray *friends;
@property (strong, nonatomic) NSMutableArray *facebookFriends;
@property (strong, nonatomic) NSArray *poiArray;
@property (nonatomic) BOOL startedFromNotification;
@property (strong, nonatomic) NSDictionary *notification;

- (void)updateMessages;

@end
