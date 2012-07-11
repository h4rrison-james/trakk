//
//  Message.m
//  STBubbleTableViewCellDemo
//
//  Created by Cedric Vandendriessche on 18/04/12.
//  Copyright 2011 FreshCreations. All rights reserved.
//

#import "Message.h"

@implementation Message

@synthesize message, avatar, author;

+ (id)messageWithString:(NSString *)msg image:(UIImage *)img author:(AuthorType)auth {
	Message *aMessage = [[Message alloc] initWithString:msg image:img author:auth];
	return aMessage;
}

- (id)initWithString:(NSString *)msg image:(UIImage *)img author:(AuthorType)auth{
	self = [super init];
	if(self)
	{
		self.message = msg;
		self.avatar = img;
        self.author = auth;
	}
	return self;
}


@end
