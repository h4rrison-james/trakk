//
//  UserViewController.m
//  Trakk
//
//  Created by Harrison Sweeney on 22/07/11.
//  Copyright 2011 Harrison J Sweeney. All rights reserved.
//

#import "UserViewController.h"

@implementation UserViewController

@synthesize table;
@synthesize friendArray;

- (utrakAppDelegate *)appDelegate {
    return (utrakAppDelegate *)[[UIApplication sharedApplication] delegate];
}

- (id)initWithStyle:(UITableViewStyle)style
{
    self = [super initWithStyle:style];
    if (self) {
        // Custom initialization
    }
    return self;
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
    [[NSNotificationCenter defaultCenter] addObserver:self selector:@selector(refreshTable) name:@"refreshNotification" object:nil];
    
    //Add shadow to navigation bar
    SET_SHADOW
    
    //Load friendArray from data controller if possible
    if ([DataController sharedClient].friendArray)
        friendArray = [DataController sharedClient].friendArray;
}

- (void)refreshTable
{ //Reload the table view
    [self.tableView reloadData];
}

- (void)viewDidUnload
{
    [self setTable:nil];
    [super viewDidUnload];
    // Release any retained subviews of the main view.
    // e.g. self.myOutlet = nil;
}

- (void)viewWillAppear:(BOOL)animated
{
    [super viewWillAppear:animated];
    [self.tableView reloadData];
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
    if ([segue.identifier isEqualToString:@"detail"])
    {
        DetailViewController *new = [segue destinationViewController];
        [new setHidesBottomBarWhenPushed:YES];
        NSIndexPath *indexPath = [[self tableView] indexPathForSelectedRow];
        PFUser *currentUser = [friendArray objectAtIndex:[indexPath row]];
        new.userID = [currentUser objectId];
        new.title = [NSString stringWithFormat:@"%@ %@", [currentUser objectForKey:@"first_name"], [currentUser objectForKey:@"last_name"]];
    }
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
    return [friendArray count];
}

- (UITableViewCell *)tableView:(UITableView *)tableView cellForRowAtIndexPath:(NSIndexPath *)indexPath
{
    static NSString *CellIdentifier = @"Cell";
    
    UserViewCellController *cell = [tableView dequeueReusableCellWithIdentifier:CellIdentifier];
    if (cell == nil) {
        cell = [[UserViewCellController alloc] initWithStyle:UITableViewCellStyleDefault reuseIdentifier:CellIdentifier];
    }
    
    PFUser *object = [friendArray objectAtIndex:indexPath.row];
    
    //Set boolean values based on PFUser values
    BOOL nameExists = [self exists:object withKey:@"name"];
    BOOL locationExists = [self exists:object withKey:@"location"];
    BOOL statusExists = [self exists:object withKey:@"status"];
    BOOL statusIsOffline = [[object objectForKey:@"status"] isEqualToString:@"Offline"];
    BOOL pictureExists = [self exists:object withKey:@"picture"];
    
    //Set name label text
    if (nameExists)
        cell.nameLabel.text = [object objectForKey:@"name"];
    else
        DLog(@"Error: No name is set for user.");
    
    //Set status label text
    NSString *status;
    NSString *statusText;
    if (statusExists)
        status = [object objectForKey:@"status"];
    else
        DLog(@"Error: No status is set for user.");
    if (locationExists && statusExists && !statusIsOffline)
    { //If location is not null and status is not null or offline, display full string
        NSString *location = [[object objectForKey:@"location"] objectForKey:@"name"];
        statusText = [NSString stringWithFormat:@"%@ @ %@", status, location];
    }
    else statusText = status;
    cell.statusLabel.text = statusText;
    
    //Set profile picture
    if (pictureExists)
    {
        PFFile *picture = [object objectForKey:@"picture"];
        NSData *data = [picture getData];
        cell.profileImage.image = [UIImage imageWithData:data];
    }
    
    //Set user ID
    cell.userID = [object objectId];
    
    //Set badge
    NSUserDefaults *defaults = [NSUserDefaults standardUserDefaults];
    if ([defaults objectForKey:@"Badges"])
    {
        NSMutableDictionary *Badges = [defaults objectForKey:@"Badges"];
        if ([Badges objectForKey:[object objectId]])
        {
            NSNumber *badge = [Badges objectForKey:[object objectId]];
            if (badge && [badge intValue] != 0)
                [cell setBadgeString:[badge stringValue]];
            else
                [cell setBadgeString:NULL];
        }
    }

	return cell;
}

#pragma mark - Table view delegate

- (void)tableView:(UITableView *)tableView didSelectRowAtIndexPath:(NSIndexPath *)indexPath
{ //Reset badge string when row is selected, and decrement tab bar badge
    UserViewCellController *cell = (UserViewCellController *)[tableView cellForRowAtIndexPath:indexPath];
    int count = [cell.badgeString intValue];
    cell.badgeString = nil;
    
    NSString *badge = [[[self.tabBarController.viewControllers objectAtIndex:1] tabBarItem] badgeValue];
    int newBadge = [badge intValue] - count;
    if (newBadge > 0)
    {
        badge = [NSString stringWithFormat:@"%d", newBadge];
        [[[self.tabBarController.viewControllers objectAtIndex:1] tabBarItem] setBadgeValue:badge];
    }
    else
        [[[self.tabBarController.viewControllers objectAtIndex:1] tabBarItem] setBadgeValue:nil];
}

-(BOOL)exists:(PFObject *)object withKey:(NSString *)key
{ //Helper method for error checking on the PFUser class
    return ([object objectForKey:key] && ![[object objectForKey:key] isKindOfClass:[NSNull class]]);
}

@end
