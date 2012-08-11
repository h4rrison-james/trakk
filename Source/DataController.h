//
//  DataController.h
//  Trakk
//
//  Created by Harrison Sweeney on 10/08/12.
//  Copyright (c) 2012 UWA. All rights reserved.
//

#import <Foundation/Foundation.h>
#import "DetailViewController.h"
#import "Parse/Parse.h"

@interface DataController : NSObject {
    //An array of PFObjects of all POI's
    NSArray *pointOfInterestArray;
    //An array of PFObjects of all inbound friend requests
    NSArray *friendRequestArray;
    //A mutable array of NSStrings of users friends ID's
    NSMutableArray *facebookFriendArray;
    //A mutable array of PFObjects of User class representing users friends
    NSMutableArray *friendArray;
}

+ (DataController *)sharedClient;
- (void)updateMessages;

@property (nonatomic, retain) NSArray *pointOfInterestArray;
@property (nonatomic, retain) NSArray *friendRequestArray;
@property (nonatomic, retain) NSMutableArray *facebookFriendArray;
@property (nonatomic, retain) NSMutableArray *friendArray;

@end
