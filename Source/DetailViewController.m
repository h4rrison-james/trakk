//
//  DetailViewController.m
//  Trakk
//
//  Created by Harrison Sweeney on 3/07/11.
//  Copyright 2011 Harrison J Sweeney. All rights reserved.
//

#import "DetailViewController.h"
#import "utrakAppDelegate.h"

@implementation DetailViewController
@synthesize messages;
@synthesize userID;
@synthesize badge;
@synthesize profile;

- (utrakAppDelegate *)appDelegate {
    return (utrakAppDelegate *)[[UIApplication sharedApplication] delegate];
}

- (void)didReceiveMemoryWarning
{
    [super didReceiveMemoryWarning];
    // Release any cached data, images, etc that aren't in use.
}

#pragma mark - View lifecycle

- (void)viewDidLoad
{
    [super viewDidLoad];
    //Set default title if title not set
    if (!self.title)
    {
        DLog(@"Error: DetailViewController title not set");
        self.title = @"Tony Stark";
    }
}

- (void)viewDidUnload
{
    [super viewDidUnload];
}

- (void)viewWillAppear:(BOOL)animated
{
    [self loadMessages];
    [self scrollToBottomAnimated:NO];
}

- (void) viewWillDisappear:(BOOL)animated
{
    //Reset badge count before view is dismissed
    badge = [NSNumber numberWithInt:0];
    [self saveMessages];
    [self updateBadge];
}

- (void)saveMessages
{
    NSUserDefaults *defaults = [NSUserDefaults standardUserDefaults];
    DLog(@"Saved messages with user ID: %@", userID);
    if ([defaults objectForKey:@"Messages"])
    {
        NSMutableDictionary *Messages = [[defaults objectForKey:@"Messages"] mutableCopy];
        [Messages setObject:messages forKey:userID];
        [defaults setObject:Messages forKey:@"Messages"];
        [defaults synchronize];
    }
    else
    {
        NSMutableDictionary *Messages = [[NSMutableDictionary alloc] initWithObjectsAndKeys:messages, userID, nil];
        [defaults setObject:Messages forKey:@"Messages"];
        [defaults synchronize];
    }
    
    //Save badge count to badge dictionary
    if ([defaults objectForKey:@"Badges"])
    {
        NSMutableDictionary *Badges = [[defaults objectForKey:@"Badges"] mutableCopy];
        [Badges setObject:badge forKey:userID];
        [defaults setObject:Badges forKey:@"Badges"];
        [defaults synchronize];
    }
    else {
        NSMutableDictionary *Badges = [[NSMutableDictionary alloc] initWithObjectsAndKeys:badge, userID, nil];
        [defaults setObject:Badges forKey:@"Badges"];
        [defaults synchronize];
    }
}

- (void)loadMessages
{
    //Check if the userDefaults has previous information in it
    NSUserDefaults *defaults = [NSUserDefaults standardUserDefaults];
    if ([defaults objectForKey:@"Messages"])
    { //Load previous messages
        NSMutableDictionary *Messages = [[defaults objectForKey:@"Messages"] mutableCopy];
        if ([Messages objectForKey:userID])
            messages = [[Messages objectForKey:userID] mutableCopy];
        else
            messages = [[NSMutableArray alloc] init];
    } 
    else
    {
        DLog(@"No previous messages exist, creating array");
        messages = [[NSMutableArray alloc] init];
    }
    
    //Restore badge count from badge dictionary
    if ([defaults objectForKey:@"Badges"])
    {
        NSMutableDictionary *Badges = [[defaults objectForKey:@"Badges"] mutableCopy];
        if ([Badges objectForKey:userID])
            badge = [Badges objectForKey:userID];
        else
            badge = [[NSNumber alloc] initWithInt:0];
    }
    else
    {
        DLog(@"No previous badges exist, creating badge");
        badge = [[NSNumber alloc] initWithInt:0];
    }
}

- (BOOL)shouldAutorotateToInterfaceOrientation:(UIInterfaceOrientation)interfaceOrientation
{
    // Return YES for supported orientations
    return (interfaceOrientation == UIInterfaceOrientationPortrait);
}

#pragma mark SSMessageViewControllerDelegate

- (Message *)messageForRowAtIndexPath:(NSIndexPath *)indexPath {
    NSDictionary *storedMessage = [messages objectAtIndex:[indexPath row]];
    
    NSString *text = [storedMessage objectForKey:@"msg"];
    AuthorType author = [[storedMessage objectForKey:@"sender"] intValue];
    UIImage *img = [[UIImage alloc] init];
    //Set image to current user profile picture if author is self
    if (author == STBubbleTableViewCellAuthorTypeSelf) {
        PFFile *file = [[PFUser currentUser] objectForKey:@"picture"];
        NSData *pictureData = [file getData];
        UIImage *avatar = [UIImage imageWithData:pictureData];
        img = avatar;
    }
    //Otherwise set image to profile picture of friend
    else if (profile == NULL) {
        img = [UIImage imageNamed:@"jonnotie"];
    }
    else {
        img = profile;
    }
    
    Message *msg = [Message alloc];
    msg = [msg initWithString:text image:img author:author];
    return msg;
}

- (void)sendButtonPressed {
    UITextView *textView = (UITextView *)[self textView];
    NSString *messageStr = textView.text;
    textView.text = @"";
    
    if([messageStr length] > 0) {
        
        NSMutableDictionary *m = [[NSMutableDictionary alloc] init];
        [m setObject:messageStr forKey:@"msg"];
        [m setObject:@"0" forKey:@"sender"];
        
        [messages addObject:m];
        int value = [badge intValue];
        value = value + 1;
        badge = [NSNumber numberWithInt:value];
        
        //Send push notification off
        NSMutableDictionary *data = [NSMutableDictionary dictionary];
        
        //Set alert dictionary
        NSString *finalMessage = [NSString stringWithFormat:@"%@: %@", [[PFUser currentUser] objectForKey:@"first_name"], messageStr];
        NSMutableDictionary *alert = [NSMutableDictionary dictionary];
        [alert setObject:finalMessage forKey:@"body"];
        [alert setObject:@"Default-Launch" forKey:@"launch-image"];
        [data setObject:alert forKey:@"alert"];
        
        //Set other options
        [data setObject:@"ping.caf" forKey:@"sound"];
        [data setObject:@"msg" forKey:@"type"];
        [data setObject:[[PFUser currentUser] objectId] forKey:@"sender"];
        [data setObject:self.title forKey:@"name"];
        [data setObject:@"Increment" forKey:@"badge"];
        [PFPush sendPushDataToChannelInBackground:userID withData:data];
        
        //Send message to server for safekeeping
        PFObject *message = [[PFObject alloc] initWithClassName:@"Messages"];
        [message setObject:finalMessage forKey:@"text"];
        [message setObject:[[PFUser currentUser] objectId] forKey:@"sender"];
        [message setObject:userID forKey:@"destination"];
        [message saveEventually];
        
        //Reload data in the table view
        [[self tableView] reloadData];
        [self scrollToBottomAnimated:YES];
    }
}

- (void)newMessageReceived:(NSDictionary *)messageContent
{ //Loads new message into the saved array, and queries deletion of message from server
    
    [self loadMessages];

    //Trim down the message, removing sender information
    NSString *messageStr = [[messageContent objectForKey:@"aps"] objectForKey:@"alert"];
    NSString *originalStr = messageStr;
    NSArray *array = [messageStr componentsSeparatedByString:@": "];
    if ([array lastObject])
        messageStr = [array lastObject];
    
    //Add the message to the array
    if([messageStr length] > 0) {
        DLog(@"%@", messageStr);
        NSMutableDictionary *m = [[NSMutableDictionary alloc] init];
        [m setObject:messageStr forKey:@"msg"];
        [m setObject:userID forKey:@"sender"];
        
        [messages addObject:m];
        int value = [badge intValue];
        value = value + 1;
        badge = [NSNumber numberWithInt:value];
    }
    
    [self saveMessages];
    
    //Delete message from the server
    PFQuery *query = [PFQuery queryWithClassName:@"Messages"];
    [query whereKey:@"text" equalTo:originalStr];
    [query whereKey:@"destination" equalTo:[[PFUser currentUser] objectId]];
    [query findObjectsInBackgroundWithBlock:^(NSArray *objects, NSError *error) {
        for (PFObject *object in objects)
            [object deleteInBackground];
    }];
    
    //Reload badge if application is active already
    if ([[UIApplication sharedApplication] applicationState] == UIApplicationStateActive)
    {
        [self updateBadge];
        [[self tableView] reloadData];
        [self scrollToBottomAnimated:YES];
    }
}

- (void)updateBadge
{
    NSUserDefaults *defaults = [NSUserDefaults standardUserDefaults];
    if ([defaults objectForKey:@"Badges"])
    {
        int total = 0;
        NSMutableDictionary *Badges = [[defaults objectForKey:@"Badges"] mutableCopy];
        for (NSString *key in Badges) {
            NSNumber *number = [Badges objectForKey:key];
            //DLog(@"Number for user %@ is %d", key, [number intValue]);
            if (number && [number intValue] != 0)
                total = total + [number intValue];
        }
        if (total) {
            NSString *badgeString = [NSString stringWithFormat:@"%d", total];
            NSDictionary *dict = [NSDictionary dictionaryWithObject:badgeString forKey:@"badgeString"];
            [[NSNotificationCenter defaultCenter] postNotificationName:@"updateBadge" object:self userInfo:dict];
        }
        else {
            NSString *badgeString = @"NULL";
            NSDictionary *dict = [NSDictionary dictionaryWithObject:badgeString forKey:@"badgeString"];
            [[NSNotificationCenter defaultCenter] postNotificationName:@"updateBadge" object:self userInfo:dict];
        }
    }
}

- (void)scrollToBottomAnimated:(BOOL)animated {
    NSInteger bottomRow = [messages count] - 1;
    if (bottomRow >= 0) {
        NSIndexPath *indexPath = [NSIndexPath indexPathForRow:bottomRow inSection:0];
        [[self tableView] scrollToRowAtIndexPath:indexPath
                           atScrollPosition:UITableViewScrollPositionBottom animated:animated];
    }
}

#pragma mark UITableViewDataSource

- (NSInteger)tableView:(UITableView *)tableView numberOfRowsInSection:(NSInteger)section {
    return [messages count];
}

@end
