//
//  ClusterViewCell.m
//  Trakk
//
//  Created by Harrison Sweeney on 12/07/12.
//  Copyright (c) 2012 UWA. All rights reserved.
//

#import "ClusterViewCell.h"

@implementation ClusterViewCell

@synthesize profileImage;
@synthesize nameLabel;
@synthesize statusLabel;
@synthesize userID;

- (id)initWithStyle:(UITableViewCellStyle)style reuseIdentifier:(NSString *)reuseIdentifier
{
    self = [super initWithStyle:style reuseIdentifier:reuseIdentifier];
    if (self) {
    }
    return self;
}

@end
