//
//  SettingsController.h
//  Trakk
//
//  Created by Harrison Sweeney on 31/03/12.
//  Copyright (c) 2012 UWA. All rights reserved.
//

#import <UIKit/UIKit.h>
#import <MessageUI/MFMailComposeViewController.h>
#import "Parse/Parse.h"

@interface SettingsController : UITableViewController <MFMailComposeViewControllerDelegate>
{
    UIView *footerView;
}

- (IBAction)cancelButtonPressed:(id)sender;
- (IBAction)peopleSwitchChanged:(id)sender forEvent:(UIEvent *)event;
- (IBAction)clubSwitchChanged:(id)sender forEvent:(UIEvent *)event;
- (void)logoutButtonPressed;

@property (strong, nonatomic) IBOutlet UILabel *versionLabel;
@property (strong, nonatomic) IBOutlet UILabel *updateLabel;
@property (weak, nonatomic) IBOutlet UISwitch *peopleSwitch;
@property (weak, nonatomic) IBOutlet UISwitch *clubSwitch;

@end
