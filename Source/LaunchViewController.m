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
@synthesize pictureData;

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
    
    if([defaults boolForKey:@"notFirstRun"] && [PFUser currentUser] && [PFFacebookUtils isLinkedWithUser:[PFUser currentUser]])
    { //If it is not the first time running, and session is valid and linked with Facebook, assume logged in
        [self facebookLoginCallback];
    }
    else
    { //Move logo and fade in Facebook button
        #warning #6 Animation needs to be tweaked
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
    //Login with facebook
    NSArray *permissions = [NSArray arrayWithObjects:@"email", nil];
    [PFFacebookUtils logInWithPermissions:permissions block:^(PFUser *user, NSError *error) {
        if (!user) {
            DLog(@"Uh oh. The user cancelled the Facebook login.");
        } else if (user.isNew) {
            DLog(@"User signed up and logged in through Facebook!");
            [self facebookLoginCallback];
        } else {
            DLog(@"User logged in through Facebook!");
            [self facebookLoginCallback];
        }
    }];
    
    CGRect textFrame = titleImage.frame;
    textFrame.origin.y = titleImage.frame.origin.y + 60;
    [UIView animateWithDuration:0.5 delay:1 options:0 animations:^{
        fbButton.alpha = 0;
        titleImage.frame = textFrame;
    } completion:nil];
}

#pragma mark Facebook Login Callback

- (void)facebookLoginCallback
{ //Sends all server requests, both Facebook and Parse, and segues to main view

    //Open a Facebook connection object
    PF_FBRequestConnection *connection = [[PF_FBRequestConnection alloc] init];
    
    //Add the request for profile information to the connection object
    NSString *requestPath = @"me?fields=first_name,last_name,name";
    PF_FBRequest *requestProfile = [PF_FBRequest requestForGraphPath:requestPath];
    [connection addRequest:requestProfile completionHandler:^(PF_FBRequestConnection *connection, id result, NSError *error) {
        if (!error)
        {
            DLog(@"FB: Request recieved for profile object");
            
            [[PFUser currentUser] setObject:[result objectForKey:@"id"] forKey:@"facebookID"];
            [[PFUser currentUser] setObject:[result objectForKey:@"first_name"] forKey:@"first_name"];
            [[PFUser currentUser] setObject:[result objectForKey:@"last_name"] forKey:@"last_name"];
            [[PFUser currentUser] setObject:[result objectForKey:@"name"] forKey:@"name"];
            
            //Request the profile picture now that we have the facebook ID
            pictureData = [[NSMutableData alloc] init];
            NSString *facebookID = [[PFUser currentUser] objectForKey:@"facebookID"];
            NSURL *pictureURL = [NSURL URLWithString:[NSString stringWithFormat:@"https://graph.facebook.com/%@/picture?type=large&return_ssl_resources=1", facebookID]];
            
            NSMutableURLRequest *urlRequest = [NSMutableURLRequest requestWithURL:pictureURL
                                                                      cachePolicy:NSURLRequestUseProtocolCachePolicy
                                                                  timeoutInterval:2.0f];
            //Run network request asynchronously
            NSURLConnection *urlConnection = [[NSURLConnection alloc] initWithRequest:urlRequest delegate:self];
            [urlConnection start];
        }
        else if ([error.userInfo[PF_FBErrorParsedJSONResponseKey][@"body"][@"error"][@"type"] isEqualToString:@"OAuthException"])
        { //Access token expired. Re-authorize.
            DLog(@"FB: The session was invalidated. Re-authorizing.");
            NSArray *permissions = [NSArray arrayWithObjects:@"email", nil];
            [PFFacebookUtils logInWithPermissions:permissions target:self selector:@selector(loginCallback:error:)];
        }
        else
        {
            DLog(@"FB Error: %@", error);
        }
    }];
    
    //Add the request for the users friends
    PF_FBRequest *requestFriends = [PF_FBRequest requestForGraphPath:@"me/friends"];
    [connection addRequest:requestFriends completionHandler:^(PF_FBRequestConnection *connection, id result, NSError *error) {
        if (!error)
        {
            DLog(@"FB: Request recieved for friend object");
            
            //Process friend list into an array of facebook ID's
            NSMutableArray *data = [result objectForKey:@"data"];
            NSMutableArray *fbFriendArray = [[NSMutableArray alloc] init];
            for(NSDictionary *user in data)
            { //For each user dictionary, extract facebook ID and add to array
                [fbFriendArray addObject:user];
            }
            //Save the final array to the data controller
            [DataController sharedClient].facebookFriendArray = fbFriendArray;
            
            NSUserDefaults *defaults = [NSUserDefaults standardUserDefaults];
            if (![defaults boolForKey:@"notFirstRun"])
            { //Must be first time running, set the run flag and defaults then segue
                [self firstRun];
                [[PFUser currentUser] saveEventually];
                [self performSegueWithIdentifier:@"homeView" sender:self];
            }
            else
            { //Not the first time running, so save current user and segue
                [[PFUser currentUser] saveEventually];
                [self performSegueWithIdentifier:@"homeView" sender:self];
            }
        }
        else if ([error.userInfo[PF_FBErrorParsedJSONResponseKey][@"body"][@"error"][@"type"] isEqualToString:@"OAuthException"])
        { //Access token expired. Re-authorize.
            DLog(@"FB: The session was invalidated. Re-authorizing.");
            NSArray *permissions = [NSArray arrayWithObjects:@"email", nil];
            [PFFacebookUtils logInWithPermissions:permissions target:self selector:@selector(loginCallback:error:)];
        }
        else
        {
            DLog(@"FB Error: %@", error);
        }
    }];
    
    //Send off the requests
    [connection start];

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

#pragma mark NSURLConnection Delegate

// Called every time a chunk of the data is received
- (void)connection:(NSURLConnection *)connection didReceiveData:(NSData *)data {
    [pictureData appendData:data]; // Build the image
}

// Called when the entire image is finished downloading
- (void)connectionDidFinishLoading:(NSURLConnection *)connection {
    DLog(@"FB: Request recieved for profile picture object");
    //Save the picture to Parse, and attach to current user
    PFFile *file = [PFFile fileWithName:@"picture" data:pictureData];
    [file saveInBackgroundWithBlock:^(BOOL succeeded, NSError *error) {
        [[PFUser currentUser] setObject:file forKey:@"picture"];
    }];
}

-(void)firstRun
{ //Set application default preferences
    NSUserDefaults *defaults = [NSUserDefaults standardUserDefaults];
    [defaults setBool:TRUE forKey:@"notFirstRun"];
    [defaults setBool:TRUE forKey:@"showPeopleOnMap"];
    [defaults setBool:FALSE forKey:@"showClubsOnMap"];
    [defaults synchronize];
}

@end
