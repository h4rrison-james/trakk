//
//  updateIntervalController.m
//  Trakk
//
//  Created by Harrison Sweeney on 6/04/12.
//  Copyright (c) 2012 UWA. All rights reserved.
//

#import "updateIntervalController.h"

@interface updateIntervalController ()

@end

@implementation updateIntervalController
@synthesize checkedPath;

- (void)viewDidLoad
{
    [super viewDidLoad];

    //Set shadow on navigation bar
    SET_SHADOW
}

-(void)viewWillDisappear:(BOOL)animated
{ //Save selected index path and corresponding update interval to user defaults
    NSUserDefaults *defaults = [NSUserDefaults standardUserDefaults];
    NSString *checkedPathString = [NSString stringWithFormat:@"%d", [checkedPath row]];
    [defaults setObject:checkedPathString forKey:@"updateIndexPath"];
    switch ([checkedPath row])
    { //Set update interval depending on row selected
        case 0:
            [defaults setObject:@"120" forKey:@"updateInterval"];
            break;
        case 1:
            [defaults setObject:@"300" forKey:@"updateInterval"];
            break;
        case 2:
            [defaults setObject:@"600" forKey:@"updateInterval"];
            break;
        default:
            [defaults setObject:@"600" forKey:@"updateInterval"];
            break;
    }
    [defaults synchronize];
}

-(void)viewWillAppear:(BOOL)animated
{
    [super viewWillAppear:animated];
    NSUserDefaults *defaults = [NSUserDefaults standardUserDefaults];
    if ([defaults objectForKey:@"updateIndexPath"])
    { //Check appropriate row and update the checkedPath variable
        NSString *checkedPathString = [defaults objectForKey:@"updateIndexPath"];
        checkedPath = [NSIndexPath indexPathForRow:[checkedPathString intValue] inSection:0];
        [[self.tableView cellForRowAtIndexPath:checkedPath] setAccessoryType:UITableViewCellAccessoryCheckmark];
    }
    else {
        checkedPath = [NSIndexPath indexPathForRow:2 inSection:0];
        [[self.tableView cellForRowAtIndexPath:checkedPath] setAccessoryType:UITableViewCellAccessoryCheckmark];
    }
}

- (BOOL)shouldAutorotateToInterfaceOrientation:(UIInterfaceOrientation)interfaceOrientation
{
    return (interfaceOrientation == UIInterfaceOrientationPortrait);
}

#pragma mark - Table view delegate

- (void)tableView:(UITableView *)tableView didSelectRowAtIndexPath:(NSIndexPath *)indexPath
{
    if (checkedPath)
    { //Remove check from checked path
        [[self.tableView cellForRowAtIndexPath:checkedPath] setAccessoryType:UITableViewCellAccessoryNone];
    }
    //Set checkmark on new path
    [[self.tableView cellForRowAtIndexPath:indexPath] setAccessoryType:UITableViewCellAccessoryCheckmark];
    //Update checkedPath
    checkedPath = indexPath;
    [self.tableView deselectRowAtIndexPath:indexPath animated:YES];
}

@end
