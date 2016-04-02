//
//  Message.m
//  STBubbleTableViewCellDemo
//
//  Created by Cedric Vandendriessche on 18/04/12.
//  Copyright 2011 FreshCreations. All rights reserved.
//

#import "Message.h"

@implementation Message

@synthesize message, avatar;

+ (id)messageWithString:(NSString *)msg {
	return [Message messageWithString:msg image:nil];
}

+ (id)messageWithString:(NSString *)msg image:(UIImage *)img {
	Message *aMessage = [[Message alloc] initWithString:msg image:img];
	return aMessage;
}

- (id)initWithString:(NSString *)msg {
	return [self initWithString:msg image:nil];
}

- (id)initWithString:(NSString *)msg image:(UIImage *)img {
	self = [super init];
	if(self)
	{
		self.message = msg;
		self.avatar = img;
	}
	return self;
}


@end
