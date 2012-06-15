//
//  UserViewCellController.h
//  Trakk
//
//  Created by Harrison Sweeney on 22/07/11.
//  Copyright 2011 Harrison J Sweeney. All rights reserved.
//

#import <UIKit/UIKit.h>
#import "DetailViewController.h"
#import "TDBadgedCell.h"

@interface UserViewCellController : TDBadgedCell {
    UIImageView *profileImage;
    UIImageView *online;
    UILabel *nameLabel;
    UILabel *statusLabel;
}

@property (nonatomic, strong) IBOutlet UIImageView *profileImage;
@property (nonatomic, strong) IBOutlet UIImageView *online;
@property (nonatomic, strong) IBOutlet UILabel *nameLabel;
@property (nonatomic, strong) IBOutlet UILabel *statusLabel;
@property (nonatomic, strong) NSString *userID;

@end
