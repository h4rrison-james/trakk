//
//  DetailViewController.h
//  utrak
//
//  Created by Harrison Sweeney on 3/07/11.
//  Copyright 2011 UWA. All rights reserved.
//

//Import Frameworks
#import <UIKit/UIKit.h>
#import <MapKit/MapKit.h>
#include <QuartzCore/QuartzCore.h>
#import "SSMessagesViewController.h"
#import "Parse/Parse.h"

@interface DetailViewController : SSMessagesViewController {
    NSMutableArray *messages;
    NSNumber *badge;
    NSString *userID;
}

@property (nonatomic, strong) NSMutableArray *messages;
@property (nonatomic) NSNumber *badge;
@property (nonatomic, strong) NSString *userID;
- (void)newMessageReceived:(NSDictionary *)messageContent;
- (void)saveMessages;
- (void)loadMessages;
- (void)updateBadge;
- (void)scrollToBottomAnimated:(BOOL)animated;

@end
