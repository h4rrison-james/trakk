//
//  FirstViewController.h
//  Trakk
//
//  Created by Harrison Sweeney on 28/09/11.
//  Copyright (c) 2011 Harrison J Sweeney. All rights reserved.
//

//Import Frameworks
#import <UIKit/UIKit.h>
#import <MapKit/MapKit.h>
#import <QuartzCore/QuartzCore.h>
#import "Parse/Parse.h"

//Import Custom Headers
#import "LocationController.h"
#import "DetailViewController.h"
#import "FirstViewCellController.h"
#import "NSDate+Format.h"
#import "utrakAppDelegate.h"
#import "DataController.h"
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
