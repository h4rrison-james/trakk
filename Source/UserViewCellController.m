//
//  UserViewCellController.m
//  Trakk
//
//  Created by Harrison Sweeney on 22/07/11.
//  Copyright 2011 Harrison J Sweeney. All rights reserved.
//

#import "UserViewCellController.h"

@implementation UserViewCellController

@synthesize profileImage;
@synthesize nameLabel;
@synthesize statusLabel;
@synthesize online;
@synthesize userID;

- (id)initWithStyle:(UITableViewCellStyle)style reuseIdentifier:(NSString *)reuseIdentifier
{
    self = [super initWithStyle:style reuseIdentifier:reuseIdentifier];
    if (self) {
        [[NSNotificationCenter defaultCenter] addObserver:self selector:@selector(pushNotificationReceived:) name:@"pushNotification" object:nil];
    }
    return self;
}

@end
