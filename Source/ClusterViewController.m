//
//  ClusterViewController.m
//  Trakk
//
//  Created by Harrison Sweeney on 11/07/12.
//  Copyright (c) 2012 UWA. All rights reserved.
//

#import "ClusterViewController.h"

@interface ClusterViewController ()

@end

@implementation ClusterViewController

@synthesize annotations;

- (id)initWithStyle:(UITableViewStyle)style
{
    self = [super initWithStyle:style];
    if (self) {
        // Custom initialization
    }
    return self;
}

- (void)viewDidLoad
{
    [super viewDidLoad];

    // Uncomment the following line to preserve selection between presentations.
    // self.clearsSelectionOnViewWillAppear = NO;
 
    // Uncomment the following line to display an Edit button in the navigation bar for this view controller.
    // self.navigationItem.rightBarButtonItem = self.editButtonItem;
}

- (void)viewDidUnload
{
    [super viewDidUnload];
    // Release any retained subviews of the main view.
    // e.g. self.myOutlet = nil;
}

- (BOOL)shouldAutorotateToInterfaceOrientation:(UIInterfaceOrientation)interfaceOrientation
{
    return (interfaceOrientation == UIInterfaceOrientationPortrait);
}

#pragma mark - Table view data source

- (NSInteger)numberOfSectionsInTableView:(UITableView *)tableView
{
    return 1;
}

- (NSInteger)tableView:(UITableView *)tableView numberOfRowsInSection:(NSInteger)section
{
    return [annotations count];
}

- (UITableViewCell *)tableView:(UITableView *)tableView cellForRowAtIndexPath:(NSIndexPath *)indexPath
{
    static NSString *CellIdentifier = @"clusterCell";
    
    ClusterViewCell *cell = [tableView dequeueReusableCellWithIdentifier:CellIdentifier];
    if (cell == nil) 
    {
        cell = [[ClusterViewCell alloc] initWithStyle:UITableViewCellStyleDefault reuseIdentifier:CellIdentifier];
    }
    
    userAnnotation *annotation = [annotations objectAtIndex:[indexPath row]];
    
    cell.nameLabel.text = annotation.title;
    cell.statusLabel.text = annotation.subtitle;
    cell.profileImage.image = annotation.image;
    PFUser *user = annotation.user;
    cell.userID = [user objectId];
    
    return cell;
}

#pragma mark - Table view delegate

- (void)tableView:(UITableView *)tableView didSelectRowAtIndexPath:(NSIndexPath *)indexPath
{
    DetailViewController *new = [[DetailViewController alloc] init];
    [new setHidesBottomBarWhenPushed:YES];
    ClusterViewCell *cell = (ClusterViewCell *)[self tableView:tableView cellForRowAtIndexPath:indexPath];
    new.userID = cell.userID;
    new.title = cell.nameLabel.text;
    self.navigationItem.backBarButtonItem =
    [[UIBarButtonItem alloc] initWithTitle:@"Back"
                                      style:UIBarButtonItemStyleBordered
                                     target:nil
                                     action:nil];
    [self.navigationController pushViewController:new animated:YES];
}

@end
