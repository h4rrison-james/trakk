//
//  trakkAppDelegate.m
//  Trakk
//
//  Created by Harrison Sweeney on 24/06/11.
//  Copyright 2011 Harrison J Sweeney. All rights reserved.
//

#import "utrakAppDelegate.h"
#import "Constants.h"

@implementation utrakAppDelegate

@synthesize window = _window;
@synthesize permissions;
@synthesize friends;
@synthesize facebookFriends;
@synthesize poiArray;
@synthesize startedFromNotification;
@synthesize notification;

- (BOOL)application:(UIApplication *)application didFinishLaunchingWithOptions:(NSDictionary *)launchOptions
{
    //Set started from notification to false to begin with
    startedFromNotification = FALSE;
    
    //Set facebookFriends and friends array from user defaults
    NSUserDefaults *defaults = [NSUserDefaults standardUserDefaults];
    if ([defaults objectForKey:@"facebookFriends"]) {
        facebookFriends = [defaults objectForKey:@"facebookFriends"];
    }
    if ([defaults objectForKey:@"friendArray"]) {
        NSData *archive = [defaults objectForKey:@"friendArray"];
        friends = [NSKeyedUnarchiver unarchiveObjectWithData:archive];
    }
    
    //Check is application was started in response to a push notification
    NSDictionary *notif = [launchOptions valueForKey:UIApplicationLaunchOptionsRemoteNotificationKey];
    if (notif)
    { //Process notification and delete message from server
        DLog(@"Notification Recieved in AD");
        startedFromNotification = TRUE;
        notification = notif;
    }
    
    //Reset badge
    [[UIApplication sharedApplication] setApplicationIconBadgeNumber:0];
    
    //Initialize Parse with Facebook
    [PFFacebookUtils initializeWithApplicationId:@"233332170018612"];
    
    //Set custom navigation bar background
    [[UINavigationBar appearance] setBackgroundImage:[UIImage imageNamed:@"Navigation-Bar"] forBarMetrics:UIBarMetricsDefault];
    
    //Set custom table view background
    [[UITableView appearance] setBackgroundColor:[UIColor colorWithPatternImage:[UIImage imageNamed:@"Background-Pattern"]]];
    
    //Set tab bar image highlight color
    UIColor *green = [UIColor colorWithRed:0.2 green:0.8 blue:0.2 alpha:1];
    [[UITabBar appearance] setSelectedImageTintColor:green];
    [[UINavigationBar appearance] setTintColor:green];
    
    [self.window makeKeyAndVisible];
    return YES;
}

- (void)application:(UIApplication *)application didReceiveRemoteNotification:(NSDictionary *)userInfo
{
    DLog(@"Notification Recieved in AD");
    [[NSNotificationCenter defaultCenter] postNotificationName:@"pushNotification" object:nil userInfo:userInfo];
}

- (void)applicationWillResignActive:(UIApplication *)application
{
    //Do Nothing
}

- (void)applicationDidEnterBackground:(UIApplication *)application
{
    if ([PFUser currentUser])
    { //Only set background task if currently logged in
        [[LocationController sharedClient] updateDelegate:nil];
        [[UIApplication sharedApplication] beginBackgroundTaskWithExpirationHandler:^{
            //Start the location manager to reset the background timer
            [[LocationController sharedClient] start];
            DLog(@"Location Controller restarted to avoid termination.");
        }];
        
        //Stop the location manager and wait out the time interval
        [[LocationController sharedClient] stop];
        NSTimeInterval interval;
        NSUserDefaults *defaults = [NSUserDefaults standardUserDefaults];
        if ([defaults objectForKey:@"updateInterval"])
            interval = [[defaults objectForKey:@"updateInterval"] doubleValue];
        else 
            interval = UPDATE_INTERVAL;
        [[LocationController sharedClient] performSelector:@selector(start) withObject:nil afterDelay:interval];
        DLog(@"Application Entered Background with interval %f", interval);
    }
}

- (void)applicationWillEnterForeground:(UIApplication *)application
{
    DLog(@"Application Entered Foreground");
    
    if ([PFUser currentUser])
    { //Check that user is logged in and not returning from login page
        //Restore location controllers previous delegate
        id delegate = [[LocationController sharedClient] previousDelegate];
        [[LocationController sharedClient] updateDelegate:delegate];
    
        PFQuery *query = [PFQuery queryWithClassName:@"Messages"];
        [query whereKey:@"destination" equalTo:[[PFUser currentUser] objectId]];
        [query orderByAscending:@"createdAt"];
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
    }
}

- (void)applicationDidBecomeActive:(UIApplication *)application
{
    //Reset badge
    [[UIApplication sharedApplication] setApplicationIconBadgeNumber:0];
}

- (void)applicationWillTerminate:(UIApplication *)application
{
    NSUserDefaults *defaults = [NSUserDefaults standardUserDefaults];
    [defaults setObject:facebookFriends forKey:@"facebookFriends"];
}

#pragma mark Facebook Callback

- (BOOL)application:(UIApplication *)application handleOpenURL:(NSURL *)url {
    return [[PFFacebookUtils facebook] handleOpenURL:url];
}

- (BOOL)application:(UIApplication *)application openURL:(NSURL *)url
  sourceApplication:(NSString *)sourceApplication annotation:(id)annotation {
    return [[PFFacebookUtils facebook] handleOpenURL:url]; 
}
    
#pragma mark Push Notifications Callback
    
- (void)application:(UIApplication *)applicationdidRegisterForRemoteNotificationsWithDeviceToken:(NSData *)newDeviceToken
{
    // Tell Parse about the device token.
    [PFPush storeDeviceToken:newDeviceToken];
    // Subscribe to the global broadcast channel.
    [PFPush subscribeToChannelInBackground:@""];
    NSString *objectID = [[PFUser currentUser] objectId];
    DLog(@"Registered for push notifications on channel: %@", objectID);
    [PFPush subscribeToChannelInBackground:objectID];
}

- (void)application:(UIApplication *)application didFailToRegisterForRemoteNotificationsWithError:(NSError *)error
{
    if ([error code] == 3010)
        NSLog(@"Push notifications don't work in the simulator!");
    else
        NSLog(@"didFailToRegisterForRemoteNotificationsWithError: %@", error);
}

@end
