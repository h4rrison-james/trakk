//
//  LaunchViewController.h
//  Trakk
//
//  Created by Harrison Sweeney on 24/07/11.
//  Copyright 2011 Harrison J Sweeney. All rights reserved.
//

#import <UIKit/UIKit.h>
#import "Parse/Parse.h"
#import "LocationController.h"
#import "utrakAppDelegate.h"

@interface LaunchViewController : UIViewController <PF_FBRequestDelegate, UIAlertViewDelegate> {
    IBOutlet UIImageView *titleImage;
    IBOutlet UIButton *fbButton;
    IBOutlet UIImageView *backgroundImage;
}

- (IBAction)facebookButtonPressed:(id)sender;
- (void)facebookLoginCallback;
@property (strong, nonatomic) IBOutlet UIActivityIndicatorView *activity;
@property (strong, nonatomic) IBOutlet UIButton *fbButton;
@property (nonatomic) BOOL startedFromNotification;

@end
