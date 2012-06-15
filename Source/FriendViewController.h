//
//  FriendViewController.h
//  Trakk
//
//  Created by Harrison Sweeney on 3/07/11.
//  Copyright 2011 Harrison J Sweeney. All rights reserved.
//

//Import Frameworks
#import <UIKit/UIKit.h>

//Import Custom Headers
#import "utrakAppDelegate.h"

@interface FriendViewController : UIViewController
<UITableViewDelegate, UITableViewDataSource, UINavigationControllerDelegate, PF_FBDialogDelegate> {
    UITableView *table;
    NSMutableArray *friendArray;
    NSMutableArray *fbFriendArray;
}

-(IBAction)dismissModal:(id)sender;
@property (nonatomic, strong) IBOutlet UITableView *table;
@property (nonatomic, strong) NSMutableArray *friendArray;
@property (nonatomic, strong) NSMutableArray *fbFriendArray;
@property (nonatomic, strong) NSMutableDictionary *master;

@end
