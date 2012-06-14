//
//  UserViewController.h
//  utrak
//
//  Created by Harrison Sweeney on 22/07/11.
//  Copyright 2011 UWA. All rights reserved.
//

//Import Framework Headers
#import <UIKit/UIKit.h>

//Import Custom Headers
#import "MessageViewCellController.h"
#import "DetailViewController.h"
#import "utrakAppDelegate.h"

@interface MessagesViewController : UITableViewController {
    NSMutableDictionary *messagesDict;
    NSArray *friendArray;
}

@property (nonatomic, strong) NSMutableDictionary *messagesDict;
@property (nonatomic, strong) NSArray *friendArray;

@end
