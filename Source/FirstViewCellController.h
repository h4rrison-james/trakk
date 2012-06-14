//
//  FirstViewCellController.h
//  Trakk
//
//  Created by Harrison Sweeney on 30/12/11.
//  Copyright (c) 2011 UWA. All rights reserved.
//

#import <UIKit/UIKit.h>

@interface FirstViewCellController : UITableViewCell
{
    IBOutlet UILabel *statusLabel;
    IBOutlet UILabel *nameLabel;
    IBOutlet UILabel *timeLabel;
    IBOutlet UIImageView *profileImage;
}

@property (nonatomic, retain) UILabel *statusLabel;
@property (nonatomic, retain) UILabel *nameLabel;
@property (nonatomic, retain) UILabel *timeLabel;
@property (nonatomic, retain) UIImageView *profileImage;

@end
