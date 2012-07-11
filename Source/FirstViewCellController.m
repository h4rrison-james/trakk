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
    //Set current context
    CGContextRef context = UIGraphicsGetCurrentContext();
    
    //Set colours
    CGColorRef whiteColor = [UIColor colorWithRed:1.0 green:1.0 blue:1.0 alpha:0.3].CGColor; 
    CGColorRef lightGrayColor = [UIColor colorWithRed:230.0/255.0 green:230.0/255.0 blue:230.0/255.0 alpha:0.7].CGColor;
    CGColorRef separatorColor = [UIColor colorWithRed:208.0/255.0 green:208.0/255.0 blue:208.0/255.0 alpha:1.0].CGColor;
    CGColorRef shadowColor = [UIColor colorWithRed:0.2 green:0.2 blue:0.2 alpha:0.5].CGColor;
    CGColorRef borderColor = [UIColor colorWithWhite:1.0 alpha:1.0].CGColor;
    
    CGRect cellRect = self.bounds;
    
    //Draw cell gradient
    [self drawLinearGradient:context :cellRect :whiteColor :lightGrayColor];
    
    //Draw cell dividing line
    CGPoint startPoint = CGPointMake(cellRect.origin.x, cellRect.origin.y + cellRect.size.height - 1);
    CGPoint endPoint = CGPointMake(cellRect.origin.x + cellRect.size.width - 1, cellRect.origin.y + cellRect.size.height - 1);
    [self draw1PxStroke:context :startPoint :endPoint :separatorColor];
    
    //Draw white rectangle for profile image border and shadow
    CGRect profileBox = profileImage.frame;
    CGFloat borderWidth = -2.0;
    CGRect profileBorder = CGRectInset(profileBox, borderWidth, borderWidth);
    //CGPathRef roundedBorder = [self newPathForRoundedRect:profileBorder radius:3.0];
    
    CGContextSaveGState(context);
    CGContextSetShadowWithColor(context, CGSizeMake(0, 2), 3.0, shadowColor);
    CGContextSetFillColorWithColor(context, borderColor);
    //CGContextAddPath(context, roundedBorder);
    //CGContextFillPath(context);
    CGContextFillRect(context, profileBorder);
    CGContextRestoreGState(context);
    
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

-(void)draw1PxStroke:(CGContextRef) context: (CGPoint) startPoint: (CGPoint) endPoint: (CGColorRef) color
{
    
    CGContextSaveGState(context);
    CGContextSetLineCap(context, kCGLineCapSquare);
    CGContextSetStrokeColorWithColor(context, color);
    CGContextSetLineWidth(context, 1.0);
    CGContextMoveToPoint(context, startPoint.x + 0.5, startPoint.y + 0.5);
    CGContextAddLineToPoint(context, endPoint.x + 0.5, endPoint.y + 0.5);
    CGContextStrokePath(context);
    CGContextRestoreGState(context);
}

- (CGPathRef) newPathForRoundedRect:(CGRect)rect radius:(CGFloat)radius
{
	CGMutablePathRef retPath = CGPathCreateMutable();
    
	CGRect innerRect = CGRectInset(rect, radius, radius);
    
	CGFloat inside_right = innerRect.origin.x + innerRect.size.width;
	CGFloat outside_right = rect.origin.x + rect.size.width;
	CGFloat inside_bottom = innerRect.origin.y + innerRect.size.height;
	CGFloat outside_bottom = rect.origin.y + rect.size.height;
    
	CGFloat inside_top = innerRect.origin.y;
	CGFloat outside_top = rect.origin.y;
	CGFloat outside_left = rect.origin.x;
    
	CGPathMoveToPoint(retPath, NULL, innerRect.origin.x, outside_top);
    
	CGPathAddLineToPoint(retPath, NULL, inside_right, outside_top);
	CGPathAddArcToPoint(retPath, NULL, outside_right, outside_top, outside_right, inside_top, radius);
	CGPathAddLineToPoint(retPath, NULL, outside_right, inside_bottom);
	CGPathAddArcToPoint(retPath, NULL,  outside_right, outside_bottom, inside_right, outside_bottom, radius);
    
	CGPathAddLineToPoint(retPath, NULL, innerRect.origin.x, outside_bottom);
	CGPathAddArcToPoint(retPath, NULL,  outside_left, outside_bottom, outside_left, inside_bottom, radius);
	CGPathAddLineToPoint(retPath, NULL, outside_left, inside_top);
	CGPathAddArcToPoint(retPath, NULL,  outside_left, outside_top, innerRect.origin.x, outside_top, radius);
    
	CGPathCloseSubpath(retPath);
    
	return retPath;
}

@end
