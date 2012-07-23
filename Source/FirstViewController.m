//
//  FirstViewController.m
//  Trakk
//
//  Created by Harrison Sweeney on 28/09/11.
//  Copyright (c) 2011 Harrison J Sweeney. All rights reserved.
//

#import "FirstViewController.h"

@implementation FirstViewController

@synthesize statusButton;
@synthesize loginButton;

- (void)didReceiveMemoryWarning
{
    // Releases the view if it doesn't have a superview.
    [super didReceiveMemoryWarning];
    
    // Release any cached data, images, etc that aren't in use.
}

#pragma mark - View lifecycle

- (id)initWithCoder:(NSCoder *)aDecoder
{
    self = [super initWithClassName:@"User"];
    self = [super initWithCoder:aDecoder];
    if (self) {        
        // Whether the built-in pull-to-refresh is enabled
        self.pullToRefreshEnabled = YES;
        
        // Whether the built-in pagination is enabled
        self.paginationEnabled = YES;
    }
    return self;
}

- (void)viewDidLoad
{
    [super viewDidLoad];
    
    //Add shadow to navigation bar
    SET_SHADOW
    
    //Add tap recognizer to navigation bar
    UITapGestureRecognizer *recognizer = [[UITapGestureRecognizer alloc] initWithTarget:self action:@selector(didNavTap:)];
    [recognizer setNumberOfTapsRequired:2];
    [[[self navigationController] navigationBar] addGestureRecognizer:recognizer];
    [recognizer setDelegate:self];
    
    //Listen for push notifications from the application delegate
    [[NSNotificationCenter defaultCenter] addObserver:self selector:@selector(pushNotificationReceived:) name:@"pushNotification" object:nil];
    [[NSNotificationCenter defaultCenter] addObserver:self selector:@selector(updateBadge:) name:@"updateBadge" object:nil];
}

- (void)viewDidUnload
{
    [self setLoginButton:nil];
    [self setStatusButton:nil];
    [super viewDidUnload];
    // Release any retained subviews of the main view.
    // e.g. self.myOutlet = nil;
}

- (void)viewWillAppear:(BOOL)animated
{
	[table reloadData];
    [super viewWillAppear:animated];
}

- (BOOL)shouldAutorotateToInterfaceOrientation:(UIInterfaceOrientation)interfaceOrientation
{
    // Return YES for supported orientations
    return (interfaceOrientation == UIInterfaceOrientationPortrait);
}

- (IBAction)statusTap:(id)sender {
    UIActionSheet *sheet = [[UIActionSheet alloc] initWithTitle:nil delegate:self cancelButtonTitle:@"Cancel" destructiveButtonTitle:@"Offline" otherButtonTitles:@"Available", @"Studying", nil];
    [sheet showFromTabBar:self.tabBarController.tabBar];
}

- (void)actionSheet:(UIActionSheet *)actionSheet clickedButtonAtIndex:(NSInteger)buttonIndex {
    NSString *title = [actionSheet buttonTitleAtIndex:buttonIndex];
    
    //Turn off location updating if necessary
    if (![title isEqualToString:@"Cancel"])
    {
        statusButton.title = title;
        if ([title isEqualToString:@"Offline"]) {
            DLog(@"Location Awareness Off");
            [[LocationController sharedClient] stop];
            [[LocationController sharedClient] setIsUpdating:FALSE];
        }
        
        //Update the user status if not equal to cancel
        [[PFUser currentUser] setObject:title forKey:@"status"];
        [[PFUser currentUser] saveEventually];
    }
}

-(void)didNavTap:(UIGestureRecognizer *)gesture
{
    if (gesture.state != UIGestureRecognizerStateEnded) return;
    DLog(@"Nav Tap Recieved");
    if (![[LocationController sharedClient] isUpdating])
    { //If not on, turn location updating on
        DLog(@"Location Awareness On");
        statusButton.title = @"Available";
        //Update the user status
        [[PFUser currentUser] setObject:statusButton.title forKey:@"status"];
        [[PFUser currentUser] saveEventually];
        
        [[LocationController sharedClient] start];
        [[LocationController sharedClient] setIsUpdating:TRUE];
    }
    else
    { //If already on, turn location updating off
        DLog(@"Location Awareness Off");
        statusButton.title = @"Offline";
        //Update the user status
        [[PFUser currentUser] setObject:statusButton.title forKey:@"status"];
        [[PFUser currentUser] saveEventually];
        
        [[LocationController sharedClient] stop];
        [[LocationController sharedClient] setIsUpdating:FALSE];
    }
}

- (void)setTitle:(NSString *)title
{
    CGRect frame = CGRectMake(0, 0, [self.title sizeWithFont:[UIFont boldSystemFontOfSize:35.0]].width, 44);
    UILabel *titleView = (UILabel *)self.navigationItem.titleView;
    if (!titleView) {
        titleView = [[UILabel alloc] initWithFrame:frame];
        titleView.backgroundColor = [UIColor clearColor];
        titleView.font = [UIFont fontWithName:@"Franchise-Bold" size:35.0];
        titleView.shadowColor = [UIColor colorWithWhite:0.0 alpha:0.5];
        titleView.textColor = [UIColor whiteColor];
        self.navigationItem.titleView = titleView;
    }
    titleView.text = title;
    [titleView sizeToFit];
}

#pragma mark - Table view data source

- (PFQuery *)queryForTable {
    PFQuery *query = [PFUser query];
    
    if ([self.objects count] == 0) {
        query.cachePolicy = kPFCachePolicyCacheThenNetwork;
    }
    
    utrakAppDelegate *delegate = [[UIApplication sharedApplication] delegate];
    NSArray *facebookFriends = [delegate facebookFriends];
    NSMutableArray *facebookIDs = [[NSMutableArray alloc] init];
    for (NSDictionary *user in facebookFriends)
    {
        [facebookIDs addObject:[user objectForKey:@"id"]];
    }
    [query whereKey:@"facebookID" containedIn:facebookIDs]; //If one of your Facebook friends
    [query includeKey:@"location"]; //Include location information
    PFGeoPoint *currentLocation = [[PFUser currentUser] objectForKey:@"coordinates"];
    if (currentLocation && ![currentLocation isKindOfClass:[NSNull class]])
    { //Order by those closest to you if you have a location set already, otherwise default order.
        [query whereKey:@"coordinates" nearGeoPoint:[[PFUser currentUser] objectForKey:@"coordinates"]];
    }
    return query;
}

- (void)objectsDidLoad:(NSError *)error {
    [super objectsDidLoad:error];
    if (error) DLog(@"Error: %@", error);
    utrakAppDelegate *delegate = [[UIApplication sharedApplication] delegate];
    [delegate setFriends:[[self objects] mutableCopy]];
    
    //Alert other views to refresh
    [[NSNotificationCenter defaultCenter] postNotificationName:@"refreshNotification" object:nil userInfo:nil];
    
    //Refresh table view
    [self.tableView reloadData];
}

- (UITableViewCell *)tableView:(UITableView *)tableView cellForRowAtIndexPath:(NSIndexPath *)indexPath object:(PFObject *)object
{
    static NSString *CellIdentifier = @"Cell";
    FirstViewCellController *cell = [tableView dequeueReusableCellWithIdentifier:CellIdentifier];
    if (cell == nil) {
        cell = [[FirstViewCellController alloc] initWithStyle:UITableViewCellStyleDefault reuseIdentifier:CellIdentifier];
    }
    
    //Set boolean values based on PFUser values
    BOOL nameExists = [self exists:object withKey:@"name"];
    BOOL locationExists = [self exists:object withKey:@"location"];
    BOOL statusExists = [self exists:object withKey:@"status"];
    BOOL updatedExists = object.updatedAt && ![object.updatedAt isKindOfClass:[NSNull class]];
    BOOL pictureExists = [self exists:object withKey:@"picture"];
    BOOL statusIsOffline = [[object objectForKey:@"status"] isEqualToString:@"Offline"];
    
    //Set name label text
    if (nameExists)
        cell.nameLabel.text = [object objectForKey:@"name"];
    else
        DLog(@"Error: No name is set for user.");
    //Set status label text
    NSString *status;
    __block NSString *statusText;
    if (statusExists)
        status = [object objectForKey:@"status"];
    else
        DLog(@"Error: No status is set for user.");
    if (locationExists && statusExists && !statusIsOffline)
    { //If location is not null and status is not null or offline, display full string
        PFObject *location = [object objectForKey:@"location"];
        [location fetchIfNeededInBackgroundWithBlock:^(PFObject *locationObject, NSError *error) {
            NSString *location = [locationObject objectForKey:@"name"];
            statusText = [NSString stringWithFormat:@"%@ @ %@", status, location];
        }];
    }
    else statusText = status;
    cell.statusLabel.text = statusText;
    //Set time label text
    if (updatedExists)
    { //Set date with relative time if timestamp exists
        NSDate *updated = object.updatedAt;
        cell.timeLabel.text = [updated formatRelativeTime];
    }
    //Set profile picture
    if (pictureExists)
    { //Set picture if remote picture exists
        PFFile *picture = [object objectForKey:@"picture"];
        NSData *data = [picture getData];
        cell.profileImage.image = [UIImage imageWithData:data];
        cell.profileImage.layer.masksToBounds = TRUE;
        //cell.profileImage.layer.cornerRadius = 3.0f;
    }
    
    return cell;
}

#pragma mark - Table view delegate

- (void)prepareForSegue:(UIStoryboardSegue *)segue sender:(id)sender
{
    if ([segue.identifier isEqualToString:@"detail"])
    { //Segue to detail view controller, setting ID and title
        DetailViewController *new = [segue destinationViewController];
        [new setHidesBottomBarWhenPushed:YES];
        NSIndexPath *indexPath = [[self tableView] indexPathForSelectedRow];
        PFUser *currentUser = [self.objects objectAtIndex:[indexPath row]];
        
        //Set profile picture based on current cell
        FirstViewCellController *cell = (FirstViewCellController *)[[self tableView] cellForRowAtIndexPath:indexPath];
        new.profile = cell.profileImage.image;
        
        new.userID = [currentUser objectId];
        new.title = [NSString stringWithFormat:@"%@ %@", [currentUser objectForKey:@"first_name"], [currentUser objectForKey:@"last_name"]];
    }
    else if ([segue.identifier isEqualToString:@"launch"])
    { //Log out user then transition
        DLog(@"User logged out");
        [PFUser logOut];
    }
}

#pragma  mark - Push Notification Delegate

- (void)pushNotificationReceived:(NSNotification *)notification
{
    DLog(@"Notification Recieved in First VC");
    NSDictionary *userInfo = [notification userInfo];
    NSString *notificationType = [userInfo objectForKey:@"type"];
    if ([notificationType isEqualToString:@"msg"])
    { //Notification is a message
        DLog(@"Notification is a message");
        
        //Setup the detail view controller, but do not save the message
        DetailViewController *temp = [[DetailViewController alloc] init];
        temp.userID = [userInfo objectForKey:@"sender"];
        temp.title = [userInfo objectForKey:@"name"];
        temp.hidesBottomBarWhenPushed = YES;
        
        if ([[UIApplication sharedApplication] applicationState] == UIApplicationStateActive)
        { //Application is already running
            DLog(@"Application already running");
            [self.tableView reloadData];
        }
        else
        { //Application was in background, present modal view controller
            DLog(@"Application launched with notification");
            self.tabBarController.selectedIndex = 0;
            [self.navigationController popToRootViewControllerAnimated:NO];
            [self.navigationController pushViewController:temp animated:NO];
            [temp scrollToBottomAnimated:NO];
        }
    }
}

- (void) updateBadge:(NSNotification *)notification
{
    NSDictionary *dict = [notification userInfo];
    NSString *badgeString = [dict objectForKey:@"badgeString"];
    if ([badgeString isEqualToString:@"NULL"])
        [[[self.tabBarController.viewControllers objectAtIndex:1] tabBarItem] setBadgeValue:NULL];
    else
        [[[self.tabBarController.viewControllers objectAtIndex:1] tabBarItem] setBadgeValue:badgeString];
}

-(BOOL)exists:(PFObject *)object withKey:(NSString *)key
{ //Helper method for error checking on the PFUser class
    return ([object objectForKey:key] && ![[object objectForKey:key] isKindOfClass:[NSNull class]]);
}

@end
