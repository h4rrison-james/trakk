//
//  ClusterViewCell.h
//  Trakk
//
//  Created by Harrison Sweeney on 12/07/12.
//  Copyright (c) 2012 UWA. All rights reserved.
//

#import <UIKit/UIKit.h>
//#import "DetailViewController.h"

@interface ClusterViewCell : UITableViewCell {
    UIImageView *profileImage;
    UILabel *nameLabel;
    UILabel *statusLabel;
}

@property (nonatomic, strong) IBOutlet UIImageView *profileImage;
@property (nonatomic, strong) IBOutlet UILabel *nameLabel;
@property (nonatomic, strong) IBOutlet UILabel *statusLabel;
@property (nonatomic, strong) NSString *userID;

@end
