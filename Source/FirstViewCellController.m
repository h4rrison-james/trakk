//
//  FirstViewCellController.m
//  Trakk
//
//  Created by Harrison Sweeney on 30/12/11.
//  Copyright (c) 2011 Harrison J Sweeney. All rights reserved.
//

#import "FirstViewCellController.h"
#import "Foundation/Foundation.h"

@implementation FirstViewCellController

@synthesize statusLabel;
@synthesize nameLabel;
@synthesize timeLabel;
@synthesize profileImage;

-(void)drawRect:(CGRect)rect
{
    CGContextRef context = UIGraphicsGetCurrentContext();
    
    CGColorRef whiteColor = [UIColor colorWithRed:1.0 green:1.0 
                                             blue:1.0 alpha:0.5].CGColor; 
    CGColorRef lightGrayColor = [UIColor colorWithRed:230.0/255.0 green:230.0/255.0 
                                                 blue:230.0/255.0 alpha:0.5].CGColor;
    
    CGRect paperRect = self.bounds;
    
    [self drawLinearGradient:context :paperRect :whiteColor :lightGrayColor];
}

-(void)drawLinearGradient:(CGContextRef) context: (CGRect) rect: (CGColorRef) startColor: (CGColorRef)  endColor;
{
    CGColorSpaceRef colorSpace = CGColorSpaceCreateDeviceRGB();
    CGFloat locations[] = { 0.0, 1.0 };
    
    NSArray *colors = [NSArray arrayWithObjects:(__bridge id)startColor, (__bridge id)endColor, nil];
    
    CGGradientRef gradient = CGGradientCreateWithColors(colorSpace, 
                                                        (__bridge CFArrayRef) colors, locations);
    CGPoint startPoint = CGPointMake(CGRectGetMidX(rect), CGRectGetMinY(rect));
    CGPoint endPoint = CGPointMake(CGRectGetMidX(rect), CGRectGetMaxY(rect));
    
    CGContextSaveGState(context);
    CGContextAddRect(context, rect);
    CGContextClip(context);
    CGContextDrawLinearGradient(context, gradient, startPoint, endPoint, 0);
    CGContextRestoreGState(context);
    
    CGGradientRelease(gradient);
    CGColorSpaceRelease(colorSpace);
}

@end
