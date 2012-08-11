//
//  STMessage.h
//  STBubbleTableViewCellDemo
//
//  Created by Cedric Vandendriessche on 18/04/12.
//  Copyright 2011 FreshCreations. All rights reserved.
//

#import <Foundation/Foundation.h>

#import "STBubbleTableViewCell.h"

@interface STMessage : NSObject {
	NSString *message;
	UIImage *avatar;
    AuthorType author;
}

+ (id)messageWithString:(NSString *)msg image:(UIImage *)img author:(AuthorType)auth;

- (id)initWithString:(NSString *)msg image:(UIImage *)img author:(AuthorType)auth;

@property (nonatomic, copy) NSString *message;
@property (nonatomic, strong) UIImage *avatar;
@property (nonatomic) AuthorType author;

@end
