//
//  userAnnotation.m
//  utrak
//
//  Created by Harrison Sweeney on 22/07/11.
//  Copyright 2011 UWA. All rights reserved.
//

#import "userAnnotation.h"

@implementation userAnnotation

@synthesize coordinate;
@synthesize pinColor;
@synthesize title;
@synthesize subtitle;
@synthesize image;
@synthesize user;

- (id)init
{
    self = [super init];
    if (self) {
        // Initialization code here.
    }
    
    return self;
}

@end
