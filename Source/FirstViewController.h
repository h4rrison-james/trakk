//
//  FirstViewController.h
//  utrak
//
//  Created by Harrison Sweeney on 28/09/11.
//  Copyright (c) 2011 UWA. All rights reserved.
//

//Import Frameworks
#import <UIKit/UIKit.h>
#import <MapKit/MapKit.h>
#import <QuartzCore/QuartzCore.h>
#import "Parse/Parse.h"
#import "Three20/Three20+Additions.h"

//Import Custom Headers
#import "LocationController.h"
#import "DetailViewController.h"
#import "FirstViewCellController.h"
#import "utrakAppDelegate.h"
#import "Constants.h"

@interface FirstViewController : PFQueryTableViewController <UIGestureRecognizerDelegate, UIActionSheetDelegate> 
{
    UIBarButtonItem *loginButton;
    UIBarButtonItem *statusButton;
    UITableView *table;
}

- (IBAction)statusTap:(id)sender;
- (void)updateBadge:(NSString *)badgeString;
@property (nonatomic, strong) IBOutlet UIBarButtonItem *loginButton;
@property (nonatomic, strong) IBOutlet UIBarButtonItem *statusButton;
@end
