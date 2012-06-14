//
//  LaunchViewController.m
//  Trakk
//
//  Created by Harrison Sweeney on 24/07/11.
//  Copyright 2011 UWA. All rights reserved.
//

#import "LaunchViewController.h"
#import "DetailViewController.h"

@implementation LaunchViewController
@synthesize activity;
@synthesize fbButton;
@synthesize startedFromNotification;

//Set request recieved values to false
BOOL FBMe = FALSE;
BOOL FBPicture = FALSE;
BOOL FBFriends = FALSE;
BOOL alertShown = FALSE;

- (void)didReceiveMemoryWarning
{
    // Releases the view if it doesn't have a superview.
    [super didReceiveMemoryWarning];
}

#pragma mark - View lifecycle

- (void)viewDidLoad
{
    [super viewDidLoad];
    //Check if application started from remote notification
    utrakAppDelegate *delegate = [[UIApplication sharedApplication] delegate];
    startedFromNotification = delegate.startedFromNotification;
    if (startedFromNotification && [PFUser currentUser])
    { //Change background image and present view controllers
        titleImage.alpha = 0;
        backgroundImage.image = [UIImage imageNamed:@"Default-Launch"];
    }
}

- (void)viewDidAppear:(BOOL)animated
{
    //Check if first time running application
    NSUserDefaults *defaults = [NSUserDefaults standardUserDefaults];
    if([defaults objectForKey:@"firstRun"] && [PFUser currentUser])
    { //If it is not the first time running, and session is valid, assume logged in
        [self facebookLoginCallback];
    }
    else
    { //Move logo and fade in Facebook button
        CGRect textFrame = titleImage.frame;
        textFrame.origin.y = titleImage.frame.origin.y - 60;
        [UIView animateWithDuration:0.7 animations:^{
            titleImage.frame = textFrame;
        } completion:^(BOOL finished) {
            [UIView animateWithDuration:0.5 animations:^{
                fbButton.alpha = 1;
            }];
        }];
    }
}

- (void)viewDidUnload
{
    [self setFbButton:nil];
    [self setActivity:nil];
    titleImage = nil;
    backgroundImage = nil;
    [super viewDidUnload];
    // Release any retained subviews of the main view.
    // e.g. self.myOutlet = nil;
}

- (void)prepareForSegue:(UIStoryboardSegue *)segue sender:(id)sender
{
    //Prepare for Segue
}

- (BOOL)shouldAutorotateToInterfaceOrientation:(UIInterfaceOrientation)interfaceOrientation
{
    // Return YES for supported orientations
    return (interfaceOrientation == UIInterfaceOrientationPortrait);
}

- (IBAction)facebookButtonPressed:(id)sender 
{
    NSArray *permissions = [NSArray arrayWithObjects:@"email", nil];
    [PFFacebookUtils logInWithPermissions:permissions target:self selector:@selector(loginCallback:error:)];
    CGRect textFrame = titleImage.frame;
    textFrame.origin.y = titleImage.frame.origin.y + 60;
    [UIView animateWithDuration:0.5 delay:1 options:0 animations:^{
        fbButton.alpha = 0;
        titleImage.frame = textFrame;
    } completion:nil];
}

#pragma mark Facebook Login Callback

- (void)loginCallback:(PFUser *)user error:(NSError *)error {
    if (error) {
        DLog(@"Error in Facebook Login: %@", error);
    }
    else if (!user) {
        DLog(@"Uh oh. The user cancelled the Facebook login.");
    }
    else if (user.isNew) {
        DLog(@"User signed up and logged in!");
        [self facebookLoginCallback];
    } 
    else {
        DLog(@"User logged in!");
        [self facebookLoginCallback];
    }  
}

- (void)facebookLoginCallback
{ //Send requests now that we are logged in

    [[PFFacebookUtils facebook] requestWithGraphPath:@"me?fields=id,first_name,middle_name,last_name,name,gender" andDelegate:self];
    [[PFFacebookUtils facebook] requestWithGraphPath:@"me/friends" andDelegate:self];
    [[PFFacebookUtils facebook] requestWithGraphPath:@"me/picture?type=large" andDelegate:self];

    //Register for Push Notifications now that we are logged in
    [[UIApplication sharedApplication] registerForRemoteNotificationTypes:UIRemoteNotificationTypeBadge|
     UIRemoteNotificationTypeAlert|
     UIRemoteNotificationTypeSound];

    //Initialize the location controller singleton
    [[LocationController sharedClient] start];
    [[LocationController sharedClient] setIsUpdating:TRUE];

    //Set status to "Available" to start
    [[PFUser currentUser] setObject:@"Available" forKey:@"status"];

    //Check for current messages on the server
    PFQuery *query = [PFQuery queryWithClassName:@"Messages"];
    [query whereKey:@"destination" equalTo:[[PFUser currentUser] objectId]];
    [query orderByAscending:@"createdAt"];
    if (startedFromNotification)
    { //Exclude notification that the application was started from in the query
        utrakAppDelegate *delegate = [[UIApplication sharedApplication] delegate];
        NSDictionary *userInfo = delegate.notification;
        NSString *message = [[userInfo objectForKey:@"aps"] objectForKey:@"alert"];
        [query whereKey:@"text" notEqualTo:message];
    }
    [query findObjectsInBackgroundWithBlock:^(NSArray *objects, NSError *error) {
        if (!error && [objects count])
        {
            for (PFObject *message in objects)
            { //Process and delete each message
                NSMutableDictionary *aps = [[NSMutableDictionary alloc] init];
                [aps setValue:[message objectForKey:@"text"] forKey:@"alert"];
                NSDictionary *mess = [[NSDictionary alloc] initWithObjectsAndKeys:aps, @"aps", nil];
                DetailViewController *detail = [[DetailViewController alloc] init];
                [detail setUserID:[message objectForKey:@"sender"]];
                [detail newMessageReceived:mess];
            }
        }
        else if (error) {
            DLog(@"Error: %@", error);
        }
    }];
    
    //Request POI array from server
    PFQuery *poiQuery = [PFQuery queryWithClassName:@"POI"];
    [poiQuery findObjectsInBackgroundWithBlock:^(NSArray *objects, NSError *error) {
        utrakAppDelegate *delegate = [[UIApplication sharedApplication] delegate];
        delegate.poiArray = objects;
    }];
}

#pragma mark Facebook Request Delegate

-(void)request:(PF_FBRequest *)request didLoad:(id)result
{ //Add data from FB request to PFUser object and save
    DLog(@"FB: Request recieved. (LVC)");
    NSString *requestType =[request.url stringByReplacingOccurrencesOfString:@"https://graph.facebook.com/" withString:@""];
    
    if ([requestType isEqualToString:@"me?fields=id,first_name,middle_name,last_name,name,gender"])
    { //FBMe loaded, add data to PFUser and save result
        DLog(@"Facebook profile object loaded");
        //Load university preference from settings
        NSUserDefaults *defaults = [NSUserDefaults standardUserDefaults];
        if ([defaults objectForKey:@"university"])
            [[PFUser currentUser] setObject:[defaults objectForKey:@"university"] forKey:@"university"];
        
        //Load remaining data from FBRequest
        [[PFUser currentUser] setObject:[result objectForKey:@"id"] forKey:@"facebookID"];
        [[PFUser currentUser] setObject:[result objectForKey:@"first_name"] forKey:@"first_name"];
        if ([result objectForKey:@"middle_name"])
            [[PFUser currentUser] setObject:[result objectForKey:@"middle_name"] forKey:@"middle_name"];
        [[PFUser currentUser] setObject:[result objectForKey:@"last_name"] forKey:@"last_name"];
        [[PFUser currentUser] setObject:[result objectForKey:@"name"] forKey:@"name"];
        [[PFUser currentUser] setObject:[result objectForKey:@"gender"] forKey:@"gender"];
        FBMe = TRUE;
    }
    else if ([requestType isEqualToString:@"me/picture?type=large"])
    { //Profile picture loaded, add data to PFUser and save result
        DLog(@"Facebook profile picture loaded");
        NSData *data = [NSData dataWithData:result];
        PFFile *file = [PFFile fileWithName:@"picture" data:data];
        [file saveInBackgroundWithBlock:^(BOOL succeeded, NSError *error) {
            [[PFUser currentUser] setObject:file forKey:@"picture"];
        }];
        FBPicture = TRUE;
    }
    else if ([requestType isEqualToString:@"me/friends"])
    { //Friend list loaded
        DLog(@"Facebook friend list loaded");
        //Process friend list into an array of facebook ID's
        NSMutableArray *data = [result objectForKey:@"data"];
        NSMutableArray *fbFriendArray = [[NSMutableArray alloc] init];
        for(NSDictionary *user in data)
        { //For each user dictionary, extract facebook ID and add to array
            [fbFriendArray addObject:user];
        }
        utrakAppDelegate *delegate = [[UIApplication sharedApplication] delegate];
        [delegate setFacebookFriends:fbFriendArray];
        FBFriends = TRUE;
    }
    else
    { //Facebook request unknown, give error message
        DLog(@"ERROR: Unknown facebook request loaded");
    }
    
    NSUserDefaults *defaults = [NSUserDefaults standardUserDefaults];
    
    if ([defaults objectForKey:@"firstRun"] && FBFriends)
    { //Not first time running, segue when "me" request is processed
        [[PFUser currentUser] saveEventually];
        [self performSegueWithIdentifier:@"homeView" sender:self];
    }
    
    if (![defaults objectForKey:@"firstRun"] && FBMe && FBPicture && FBFriends)
    { //Must be first time running, set the run flag and segue
        [defaults setObject:@"1" forKey:@"firstRun"];
        [[PFUser currentUser] saveEventually];
        [self performSegueWithIdentifier:@"homeView" sender:self];
    }
}

-(void)request:(PF_FBRequest *)request didFailWithError:(NSError *)error
{
    if ([error code] == 10000)
    { //Access token expired. Re-authorize.
        DLog(@"FB: Expired access token. Re-authorizing.");
        NSArray *permissions = [NSArray arrayWithObjects:@"offline_access", @"email", nil];
        [PFFacebookUtils logInWithPermissions:permissions target:self selector:@selector(loginCallback:error:)];
    }
    else if ([error code] == -1009)
    { //Network appears to be offline, notify user
        DLog(@"FB: Network Error");
        //Display alert and exit application.
        [self performSegueWithIdentifier:@"homeView" sender:self];
        if (!alertShown)
        {
            UIAlertView *alert = [[UIAlertView alloc] initWithTitle:@"Offline Error" message:@"It looks like you're offline. Please try again." delegate:self cancelButtonTitle:nil otherButtonTitles:@"Okay", nil];
            [alert show];
            alertShown = TRUE;
        }
    }
    else
    { //Other error.
        DLog(@"FB: Failed with error. %@", error);
    }
}

-(void)alertView:(UIAlertView *)alertView didDismissWithButtonIndex:(NSInteger)buttonIndex
{
    //Alert was dismissed.
}

@end
