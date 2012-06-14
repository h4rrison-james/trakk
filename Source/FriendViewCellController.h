//
//  FriendViewCellController.h
//  utrak
//
//  Created by Harrison Sweeney on 22/07/11.
//  Copyright 2011 UWA. All rights reserved.
//

#import <UIKit/UIKit.h>
#import "Parse/Parse.h"

@interface FriendViewCellController : UITableViewCell <PF_FBRequestDelegate> {
    UILabel *nameLabel;
    UIImageView *profileImage;
}

@property (nonatomic, strong) IBOutlet UILabel *nameLabel;
@property (nonatomic, strong) IBOutlet UIImageView *profileImage;
@property (nonatomic, strong) PF_FBRequest *request;

@end
