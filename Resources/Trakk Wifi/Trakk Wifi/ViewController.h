//
//  ViewController.h
//  Trakk Wifi
//
//  Created by Harrison Sweeney on 10/03/12.
//  Copyright (c) 2012 UWA. All rights reserved.
//

#import <UIKit/UIKit.h>

@interface ViewController : UIViewController
@property (weak, nonatomic) IBOutlet UILabel *nameLabel;
@property (weak, nonatomic) IBOutlet UILabel *bssidLabel;
@property (weak, nonatomic) IBOutlet UITextField *locationField;
- (IBAction)savePressed:(id)sender;
- (IBAction)refreshPressed:(id)sender;
@property (weak, nonatomic) IBOutlet UITextView *textView;

@end
