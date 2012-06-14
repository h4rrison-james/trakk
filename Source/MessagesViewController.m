//
//  UserViewController.m
//  utrak
//
//  Created by Harrison Sweeney on 22/07/11.
//  Copyright 2011 UWA. All rights reserved.
//

#import "MessagesViewController.h"

@implementation MessagesViewController

@synthesize messagesDict;
@synthesize  friendArray;

- (utrakAppDelegate *)appDelegate {
    return (utrakAppDelegate *)[[UIApplication sharedApplication] delegate];
}

- (void)didReceiveMemoryWarning
{
    // Releases the view if it doesn't have a superview.
    [super didReceiveMemoryWarning];
    
    // Release any cached data, images, etc that aren't in use.
}

#pragma mark - View lifecycle

- (void)viewDidLoad
{
    [super viewDidLoad];
    
    //Listen for notifications
    [[NSNotificationCenter defaultCenter] addObserver:self selector:@selector(pushNotificationReceived:) name:@"pushNotification" object:nil];
    
    //Add shadow to navigation bar
    self.navigationController.navigationBar.layer.shadowColor = [[UIColor blackColor] CGColor];
    self.navigationController.navigationBar.layer.shadowOffset = CGSizeMake(0.0, 0.5);
    self.navigationController.navigationBar.layer.masksToBounds = NO;
    self.navigationController.navigationBar.layer.shouldRasterize = YES;
    self.navigationController.navigationBar.layer.shadowOpacity = 1;
    
    //Load friendArray from application delegate if possible
    utrakAppDelegate *delegate = [[UIApplication sharedApplication] delegate];
    if ([delegate friends])
        friendArray = [delegate friends];
    
    //Load messagesDict from user defaults if possible
    NSUserDefaults *defaults = [NSUserDefaults standardUserDefaults];
    if ([defaults objectForKey:@"Messages"])
    { //Load previous messages
        messagesDict = [[defaults objectForKey:@"Messages"] mutableCopy];
    }
}

- (void)pushNotificationReceived:(NSNotification *)notification
{
    DLog(@"Notification Recieved in Friend VC");
    NSDictionary *userInfo = [notification userInfo];
    NSString *notificationType = [userInfo objectForKey:@"type"];
    if ([notificationType isEqualToString:@"msg"])
    { //Notification is a message
        DLog(@"Notification is a message");
        //Add message to saved array of messages
        DetailViewController *temp = [[DetailViewController alloc] init];
        temp.userID = [userInfo objectForKey:@"sender"];
        temp.title = [userInfo objectForKey:@"name"];
        temp.hidesBottomBarWhenPushed = YES;
        [temp newMessageReceived:userInfo];
        
        if ([[UIApplication sharedApplication] applicationState] == UIApplicationStateActive)
        { //Application is already running
            DLog(@"Application already running");
            //Increment tab bar badge value
            NSString *badge = [[[self.tabBarController.viewControllers objectAtIndex:1] tabBarItem] badgeValue];
            int newBadge = [badge intValue] + 1;
            badge = [NSString stringWithFormat:@"%d", newBadge];
            [[[self.tabBarController.viewControllers objectAtIndex:1] tabBarItem] setBadgeValue:badge];
        }
        else
        { //Application was inactive, present modal view controller
            DLog(@"Application launched with notification");
            self.tabBarController.selectedIndex = 1;
            [self.navigationController popToRootViewControllerAnimated:NO];
            [self.navigationController pushViewController:temp animated:NO];
            [temp scrollToBottomAnimated:NO];
        }
    }
}

- (void)viewDidUnload
{
    [super viewDidUnload];
    // Release any retained subviews of the main view.
    // e.g. self.myOutlet = nil;
}

- (void)viewWillAppear:(BOOL)animated
{
    [super viewWillAppear:animated];
}

- (void)viewDidAppear:(BOOL)animated
{
    [super viewDidAppear:animated];
}

- (void)viewWillDisappear:(BOOL)animated
{
    [super viewWillDisappear:animated];
}

- (void)viewDidDisappear:(BOOL)animated
{
    [super viewDidDisappear:animated];
}

- (void)prepareForSegue:(UIStoryboardSegue *)segue sender:(id)sender
{
    DetailViewController *new = [segue destinationViewController];
    [new setHidesBottomBarWhenPushed:YES];
    NSIndexPath *indexPath = [[self tableView] indexPathForSelectedRow];
    PFUser *currentUser = [friendArray objectAtIndex:[indexPath row]];
    new.userID = [currentUser objectId];
    new.title = [NSString stringWithFormat:@"%@ %@", [currentUser objectForKey:@"first_name"], [currentUser objectForKey:@"last_name"]];
}

- (BOOL)shouldAutorotateToInterfaceOrientation:(UIInterfaceOrientation)interfaceOrientation
{
    // Return YES for supported orientations
    return (interfaceOrientation == UIInterfaceOrientationPortrait);
}

#pragma mark - Table view data source

- (NSInteger)numberOfSectionsInTableView:(UITableView *)tableView
{
    // Return the number of sections.
    return 1;
}

- (NSInteger)tableView:(UITableView *)tableView numberOfRowsInSection:(NSInteger)section
{
    // Return the number of rows in the section.
    return [messagesDict count];
}

- (UITableViewCell *)tableView:(UITableView *)tableView cellForRowAtIndexPath:(NSIndexPath *)indexPath
{
    static NSString *CellIdentifier = @"Cell";
    
    MessageViewCellController *cell = [tableView dequeueReusableCellWithIdentifier:CellIdentifier];
    if (cell == nil) {
        cell = [[MessageViewCellController alloc] initWithStyle:UITableViewCellStyleDefault reuseIdentifier:CellIdentifier];
    }
    
    //Set name label
    //TODO

	return cell;
}

#pragma mark - Table view delegate

- (void)tableView:(UITableView *)tableView didSelectRowAtIndexPath:(NSIndexPath *)indexPath
{
    //Cell selected
}

@end
