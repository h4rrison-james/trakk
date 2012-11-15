//
//  FriendViewController.m
//  Trakk
//
//  Created by Harrison Sweeney on 4/07/11.
//  Copyright 2011 Harrison J Sweeney. All rights reserved.
//

#import "FriendViewController.h"
#import "FriendViewCellController.h"
#import "QuartzCore/QuartzCore.h"


@implementation FriendViewController

@synthesize table;
@synthesize fbFriendArray;
@synthesize friendArray;
@synthesize master;

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
    
    //Load existing friendArray and fbFriendArray from the data controller
    if ([DataController sharedClient].friendArray)
        friendArray = [DataController sharedClient].friendArray;
    if ([DataController sharedClient].facebookFriendArray)
        fbFriendArray = [DataController sharedClient].facebookFriendArray;
    
    //Remove friends that have already registered in the app
    NSMutableArray *temp = [[NSMutableArray alloc] init];
    for (PFUser *user in friendArray)
    { //Remove each user in friendArray one by one
        for (NSDictionary *fbUser in fbFriendArray)
        { //Remove user if facebook ID's are a match
            if ([[fbUser objectForKey:@"id"] isEqualToString:[user objectForKey:@"facebookID"]])
                [temp addObject:fbUser];
        }
    }
    [fbFriendArray removeObjectsInArray:temp];
    
    //Sort facebook friend array by first letter
    [fbFriendArray sortUsingComparator:^NSComparisonResult(id obj1, id obj2) {
        NSString *name1 = [obj1 objectForKey:@"name"];
        NSString *name2 = [obj2 objectForKey:@"name"];
        NSComparisonResult result = [name1 compare:name2];
        return result;
    }];
    
    //Arrange facebook friend array into section array
    master = [[NSMutableDictionary alloc] init];
    for (NSDictionary *user in fbFriendArray)
    {
        NSString *name = [user objectForKey:@"name"];
        NSString *first = [[name substringToIndex:1] capitalizedString];
        if (![master objectForKey:first])
        { //Letter does not exist in dictionary, add it and create array
            NSMutableArray *letterDict = [[NSMutableArray alloc] init];
            [letterDict addObject:user];
            [master setObject:letterDict forKey:first];
        }
        else
        { //Letter dictionary already exists, add user to current array
            NSMutableArray *letterDict = [master objectForKey:first];
            [letterDict addObject:user];
        }
    }
}

- (IBAction)dismissModal:(id)sender
{
    [self.presentingViewController dismissViewControllerAnimated:YES completion:nil];
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

- (BOOL)shouldAutorotateToInterfaceOrientation:(UIInterfaceOrientation)interfaceOrientation
{
    // Return YES for supported orientations
    return (interfaceOrientation == UIInterfaceOrientationPortrait);
}

#pragma mark - Table view data source

- (NSInteger)numberOfSectionsInTableView:(UITableView *)tableView
{
    // Return the number of sections.
    //NSLog(@"Master Dictionary Count: %d", [master count]);
    return [master count];
}

- (NSInteger)tableView:(UITableView *)tableView numberOfRowsInSection:(NSInteger)section
{
    // Return the number of rows in the section.
    char letter = (char)section + 97;
    NSString *key = [[NSString stringWithFormat:@"%c", letter] capitalizedString];
    //NSLog(@"Master Dictionary Row Count: %d for %@", [[master objectForKey:key] count], key);
    return [[master objectForKey:key] count];
}

- (UITableViewCell *)tableView:(UITableView *)tableView cellForRowAtIndexPath:(NSIndexPath *)indexPath
{ 
    static NSString *CellIdentifier = @"Cell";
    
     FriendViewCellController *cell = [tableView dequeueReusableCellWithIdentifier:CellIdentifier];
    if (cell == nil) {
        cell = [[FriendViewCellController alloc] initWithStyle:UITableViewCellStyleDefault reuseIdentifier:CellIdentifier];
    }
    
    //Get individual person dictionary
    char letter = (char)[indexPath section] + 97;
    NSString *key = [[NSString stringWithFormat:@"%c", letter] capitalizedString];
    NSMutableArray *array = [master objectForKey:key];
    
    //Set cell label to person name
    NSDictionary *person = [array objectAtIndex:[indexPath row]];
    NSString *name = [person objectForKey:@"name"];
    cell.nameLabel.text = name;
    
    #warning #7 This does not work correctly yet
    //Request the users profile picture
    NSURL *pictureURL = [NSURL URLWithString:[NSString stringWithFormat:@"https://graph.facebook.com/%@/picture?type=square&return_ssl_resources=1", [person valueForKey:@"id"]]];
    
    NSMutableURLRequest *urlRequest = [NSMutableURLRequest requestWithURL:pictureURL
                                                              cachePolicy:NSURLRequestUseProtocolCachePolicy
                                                          timeoutInterval:2.0f];
    // Run network request asynchronously
    NSURLConnection *urlConnection = [[NSURLConnection alloc] initWithRequest:urlRequest delegate:cell];
    [urlConnection start];
    
    return cell;
}

- (NSString *)tableView:(UITableView *)tableView titleForHeaderInSection:(NSInteger)section
{
    char letter = (char)section + 97;
    NSString *title = [[NSString stringWithFormat:@"%c", letter] capitalizedString];
    if ([[master objectForKey:title] count] > 0) return title;
    else return nil;
}

- (NSArray *)sectionIndexTitlesForTableView:(UITableView *)tableView
{
    return [[master allKeys] sortedArrayUsingSelector:@selector(compare:)];
}

#pragma mark - Table view delegate

- (void)tableView:(UITableView *)tableView didSelectRowAtIndexPath:(NSIndexPath *)indexPath
{
    //Get individual person dictionary
    char letter = (char)[indexPath section] + 97;
    NSString *key = [[NSString stringWithFormat:@"%c", letter] capitalizedString];
    NSMutableArray *array = [master objectForKey:key];
    NSDictionary *person = [array objectAtIndex:[indexPath row]];
    
    NSMutableDictionary* params = 
    [NSMutableDictionary dictionaryWithObjectsAndKeys:
        @"Invites you to start using Trakk",  @"message",
        @"Check this out", @"notification_text",
        [person objectForKey:@"id"], @"to",
     nil];
    #warning #8 Need to update for new SDK
    [[PFFacebookUtils facebook] dialog:@"apprequests" andParams:params andDelegate:self];
}

- (void)dialogDidComplete:(PF_FBDialog *)dialog {
    NSLog(@"Request sent succesfully.");
    [self.presentingViewController dismissViewControllerAnimated:YES completion:nil];
}

- (void)dialogDidNotComplete:(PF_FBDialog *)dialog {
    NSLog(@"Request did not complete.");
    [self.presentingViewController dismissViewControllerAnimated:YES completion:nil];
}

@end
