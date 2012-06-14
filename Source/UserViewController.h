//
//  UserViewController.h
//  utrak
//
//  Created by Harrison Sweeney on 22/07/11.
//  Copyright 2011 UWA. All rights reserved.
//

//Import Framework Headers
#import <UIKit/UIKit.h>

//Import Custom Headers
#import "UserViewCellController.h"
#import "DetailViewController.h"
#import "utrakAppDelegate.h"
#import "Constants.h"

@interface UserViewController : UITableViewController {
    NSArray *friendArray;
}

- (void)refreshTable;
@property (strong, nonatomic) IBOutlet UITableView *table;
@property (nonatomic, strong) NSArray *friendArray;

@end
