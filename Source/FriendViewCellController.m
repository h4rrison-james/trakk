//
//  FriendViewCellController.m
//  Trakk
//
//  Created by Harrison Sweeney on 22/07/11.
//  Copyright 2011 Harrison J Sweeney. All rights reserved.
//

#import "FriendViewCellController.h"

@implementation FriendViewCellController

@synthesize nameLabel;
@synthesize profileImage;
@synthesize request;

- (void)request:(PF_FBRequest *)request didLoad:(id)result
{ //Set profile picture
    self.request = nil;
    NSData *picture = [NSData dataWithData:result];
    UIImage *image = [UIImage imageWithData:picture];
    profileImage.image = image;
}

- (id)initWithStyle:(UITableViewCellStyle)style reuseIdentifier:(NSString *)reuseIdentifier
{
    self = [super initWithStyle:style reuseIdentifier:reuseIdentifier];
    if (self) {
        // Initialization code
    }
    return self;
}

- (void)setSelected:(BOOL)selected animated:(BOOL)animated
{
    [super setSelected:selected animated:animated];

    // Configure the view for the selected state
}

- (void)prepareForReuse
{ //If there is a pending Facebook request that has not yet been recieved, cancel it
    if (request)
        [[request connection] cancel];
}

- (void)dealloc
{ //Also remove request when deallocating to avoid crash
    if (request)
        [[request connection] cancel];
}

@end
