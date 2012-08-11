//
//  DataController.m
//  Trakk
//
//  Created by Harrison Sweeney on 10/08/12.
//  Copyright (c) 2012 UWA. All rights reserved.
//

#import "DataController.h"

@implementation DataController

@synthesize friendRequestArray;
@synthesize pointOfInterestArray;
@synthesize facebookFriendArray;
@synthesize friendArray;

static DataController *sharedClient;

+ (DataController *)sharedClient {
    @synchronized (self) {
        if (!sharedClient)
            sharedClient=[[DataController alloc] init];
    }
    return sharedClient;
}

+ (id)alloc {
    @synchronized(self) {
        NSAssert(sharedClient == nil, @"Cannot create second instance of DataController singleton");
        sharedClient = [super alloc];
    }
    return sharedClient;
}

- (void)updateMessages
{
    //Check for new messages on the server
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
        
        //Let the detail view controller know when processing is complete
        [[NSNotificationCenter defaultCenter] postNotificationName:@"messagesComplete" object:nil];
    }];
    
}

@end
