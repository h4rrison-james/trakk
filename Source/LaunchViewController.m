//
//  LaunchViewController.m
//  Trakk
//
//  Created by Harrison Sweeney on 24/07/11.
//  Copyright 2011 Harrison J Sweeney. All rights reserved.
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
    //View did load
}

- (void)viewDidAppear:(BOOL)animated
{
    //Check if first time running application
    NSUserDefaults *defaults = [NSUserDefaults standardUserDefaults];
    
    if([defaults boolForKey:@"notFirstRun"] && [PFUser currentUser])
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
    //Prepare for segue
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

    //Send Facebook requests off
    [[PFFacebookUtils facebook] requestWithGraphPath:@"me?fields=id,first_name,last_name,name" andDelegate:self];
    [[PFFacebookUtils facebook] requestWithGraphPath:@"me/friends" andDelegate:self];
    [[PFFacebookUtils facebook] requestWithGraphPath:@"me/picture?type=large" andDelegate:self];

    //Initialize the location controller singleton
    [[LocationController sharedClient] start];
    [[LocationController sharedClient] setIsUpdating:TRUE];
    
    //Register for Push Notifications
    [PFInstallation currentInstallation];
    [[UIApplication sharedApplication] registerForRemoteNotificationTypes:(UIRemoteNotificationTypeBadge
                                                                           |UIRemoteNotificationTypeAlert
                                                                           |UIRemoteNotificationTypeSound)];

    //Set status to "Available" to start
    [[PFUser currentUser] setObject:@"Available" forKey:@"status"];

    //Check for current messages on the server
    [[DataController sharedClient] updateMessages];
    
    //Check for pending friend requests
    PFQuery *frQuery = [PFQuery queryWithClassName:@"FriendRequest"];
    [frQuery whereKey:@"targetUser" equalTo:[[PFUser currentUser] objectId]];
    [frQuery findObjectsInBackgroundWithBlock:^(NSArray *objects, NSError *error) {
        [DataController sharedClient].friendRequestArray = objects;
    }];
    
    //Request POI array from server
    PFQuery *poiQuery = [PFQuery queryWithClassName:@"POI"];
    [poiQuery findObjectsInBackgroundWithBlock:^(NSArray *objects, NSError *error) {
        [DataController sharedClient].pointOfInterestArray = objects;
    }];
}

#pragma mark Facebook Request Delegate

-(void)request:(PF_FBRequest *)request didLoad:(id)result
{ //Add data from FB request to PFUser object and save
    DLog(@"FB: Request recieved. (LVC)");
    NSString *requestType =[request.url stringByReplacingOccurrencesOfString:@"https://graph.facebook.com/" withString:@""];
    
    if ([requestType isEqualToString:@"me?fields=id,first_name,last_name,name"])
    { //FBMe loaded, add data to PFUser and save result
        DLog(@"Facebook profile object loaded");
        //Load university preference from settings
        NSUserDefaults *defaults = [NSUserDefaults standardUserDefaults];
        if ([defaults objectForKey:@"university"])
            [[PFUser currentUser] setObject:[defaults objectForKey:@"university"] forKey:@"university"];
        
        //Load remaining data from FBRequest
        [[PFUser currentUser] setObject:[result objectForKey:@"id"] forKey:@"facebookID"];
        [[PFUser currentUser] setObject:[result objectForKey:@"first_name"] forKey:@"first_name"];
        [[PFUser currentUser] setObject:[result objectForKey:@"last_name"] forKey:@"last_name"];
        [[PFUser currentUser] setObject:[result objectForKey:@"name"] forKey:@"name"];
        
        FBMe = TRUE;
    }
    else if ([requestType isEqualToString:@"me/picture?type=large"])
    { //Profile picture loaded, add data to PFUser and save result
        DLog(@"Facebook profile picture loaded");
        result = request.responseText;
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
        
        [DataController sharedClient].facebookFriendArray = fbFriendArray;
        FBFriends = TRUE;
    }
    else
    { //Facebook request unknown, give error message
        DLog(@"ERROR: Unknown facebook request loaded");
    }
    
    NSUserDefaults *defaults = [NSUserDefaults standardUserDefaults];
    
    if ([defaults boolForKey:@"notFirstRun"] && FBFriends)
    { //Not first time running, segue when "me" request is processed
        [[PFUser currentUser] saveEventually];
        [self performSegueWithIdentifier:@"homeView" sender:self];
    }
    
    if (![defaults boolForKey:@"notFirstRun"] && FBMe && FBPicture && FBFriends)
    { //Must be first time running, set the run flag and defaults and segue
        [self firstRun];
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

-(void)firstRun
{ //Set application default preferences
    NSUserDefaults *defaults = [NSUserDefaults standardUserDefaults];
    [defaults setBool:TRUE forKey:@"notFirstRun"];
    [defaults setBool:TRUE forKey:@"showPeopleOnMap"];
    [defaults setBool:FALSE forKey:@"showClubsOnMap"];
    [defaults synchronize];
}

-(void)alertView:(UIAlertView *)alertView didDismissWithButtonIndex:(NSInteger)buttonIndex
{
    //Alert was dismissed.
}

@end
