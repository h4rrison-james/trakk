//
//  UserViewCellController.h
//  utrak
//
//  Created by Harrison Sweeney on 22/07/11.
//  Copyright 2011 UWA. All rights reserved.
//

#import <UIKit/UIKit.h>
#import "DetailViewController.h"

@interface MessageViewCellController : UITableViewCell {
    UILabel *nameLabel;
    UILabel *messageLabel;
    UILabel *timeLabel;
}

@property (nonatomic, strong) IBOutlet UILabel *nameLabel;
@property (nonatomic, strong) IBOutlet UILabel *messageLabel;
@property (nonatomic, strong) IBOutlet UILabel *timeLabel;

@end
