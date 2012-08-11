//
//  STBubbleTableViewCell.m
//  STBubbleTableViewCellDemo
//
//  Created by Cedric Vandendriessche on 18/04/12.
//  Copyright 2011 FreshCreations. All rights reserved.
//

#import "STBubbleTableViewCell.h"
#import <QuartzCore/QuartzCore.h>

@interface STBubbleTableViewCell (Private)
- (void)updateFramesForAuthorType:(AuthorType)type;
- (void)setImageForBubbleColor:(BubbleColor)color;
@end

@implementation STBubbleTableViewCell

@synthesize bubbleView, authorType, bubbleColor, selectedBubbleColor, canCopyContents, selectionAdjustsColor, dataSource, delegate;

- (id)initWithStyle:(UITableViewCellStyle)style reuseIdentifier:(NSString *)reuseIdentifier {
	
    self = [super initWithStyle:style reuseIdentifier:reuseIdentifier];
    if (self) {
		self.selectionStyle = UITableViewCellSelectionStyleNone;
		self.contentView.autoresizingMask = UIViewAutoresizingFlexibleWidth | UIViewAutoresizingFlexibleHeight;
		
		bubbleView = [[UIImageView alloc] initWithFrame:CGRectZero];
		bubbleView.userInteractionEnabled = YES;
		[self.contentView addSubview:bubbleView];
		
		self.textLabel.backgroundColor = [UIColor colorWithRed:0.859f green:0.886f blue:0.929f alpha:1.0f];
		self.textLabel.numberOfLines = 0;
		self.textLabel.lineBreakMode = UILineBreakModeWordWrap;
		self.textLabel.textColor = [UIColor blackColor];
		self.textLabel.font = [UIFont systemFontOfSize:14.0];
		
		self.imageView.userInteractionEnabled = YES;
		self.imageView.layer.cornerRadius = 5.0;
        self.imageView.contentMode = UIViewContentModeScaleAspectFill;
		self.imageView.layer.masksToBounds = YES;
		
		UILongPressGestureRecognizer *longPressRecognizer = [[UILongPressGestureRecognizer alloc] initWithTarget:self action:@selector(longPress:)];
		[bubbleView addGestureRecognizer:longPressRecognizer];
		
		UITapGestureRecognizer *tapRecognizer = [[UITapGestureRecognizer alloc] initWithTarget:self action:@selector(tap:)];
		[self.imageView addGestureRecognizer:tapRecognizer];
		
        // Set defaults
		selectedBubbleColor = STBubbleTableViewCellBubbleColorAqua;
		canCopyContents = YES;
		selectionAdjustsColor = YES;
    }
    return self;
}

- (void)updateFramesForAuthorType:(AuthorType)type {
	[self setImageForBubbleColor:bubbleColor];
	
	CGFloat minInset;
	if([(NSObject *)dataSource respondsToSelector:@selector(minInsetForCell:atIndexPath:)])
		minInset = [dataSource minInsetForCell:self atIndexPath:[(UITableView *)self.superview indexPathForCell:self]];
	else
		minInset = 0.0f;
	
	CGSize size;
	if(self.imageView.image)
		size = [self.textLabel.text sizeWithFont:self.textLabel.font constrainedToSize:CGSizeMake(self.frame.size.width - minInset - kSTBubbleWidthOffset - kSTBubbleImageSize - 8.0f, 1024.0f) lineBreakMode:UILineBreakModeWordWrap];
	else
		size = [self.textLabel.text sizeWithFont:self.textLabel.font constrainedToSize:CGSizeMake(self.frame.size.width - minInset - kSTBubbleWidthOffset, 1024.0f) lineBreakMode:UILineBreakModeWordWrap];
	
	// You can always play with these values if you need to
	if(type == STBubbleTableViewCellAuthorTypeSelf)
	{
		if(self.imageView.image)
		{
			bubbleView.frame = CGRectMake(self.frame.size.width - (size.width + kSTBubbleWidthOffset) - kSTBubbleImageSize - 8.0f, self.frame.size.height - (size.height + 15.0f), size.width + kSTBubbleWidthOffset, size.height + 15.0f);
			self.imageView.frame = CGRectMake(self.frame.size.width - kSTBubbleImageSize - 5.0f, self.frame.size.height - kSTBubbleImageSize - 2.0f, kSTBubbleImageSize, kSTBubbleImageSize);
			self.textLabel.frame = CGRectMake(self.frame.size.width - (size.width + kSTBubbleWidthOffset - 10.0f) - kSTBubbleImageSize - 8.0f, self.frame.size.height - (size.height + 15.0f) + 6.0f, size.width + kSTBubbleWidthOffset - 23.0f, size.height);
		}
		else
		{
			bubbleView.frame = CGRectMake(self.frame.size.width - (size.width + kSTBubbleWidthOffset), 0.0f, size.width + kSTBubbleWidthOffset, size.height + 15.0f);
			self.imageView.frame = CGRectZero;
			self.textLabel.frame = CGRectMake(self.frame.size.width - (size.width + kSTBubbleWidthOffset - 10.0f), 6.0f, size.width + kSTBubbleWidthOffset - 23.0f, size.height);
		}
		
		self.textLabel.autoresizingMask = UIViewAutoresizingFlexibleLeftMargin;
		bubbleView.autoresizingMask = UIViewAutoresizingFlexibleLeftMargin;
		bubbleView.transform = CGAffineTransformIdentity;
	}
	else
	{
		if(self.imageView.image)
		{
			bubbleView.frame = CGRectMake(kSTBubbleImageSize + 8.0f, self.frame.size.height - (size.height + 15.0f), size.width + kSTBubbleWidthOffset, size.height + 15.0f);
			self.imageView.frame = CGRectMake(5.0, self.frame.size.height - kSTBubbleImageSize - 2.0f, kSTBubbleImageSize, kSTBubbleImageSize);
			self.textLabel.frame = CGRectMake(kSTBubbleImageSize + 8.0f + 16.0f, self.frame.size.height - (size.height + 15.0f) + 6.0f, size.width + kSTBubbleWidthOffset - 23.0f, size.height);
		}
		else
		{
			bubbleView.frame = CGRectMake(0.0f, 0.0f, size.width + kSTBubbleWidthOffset, size.height + 15.0f);
			self.imageView.frame = CGRectZero;
			self.textLabel.frame = CGRectMake(16.0f, 6.0f, size.width + kSTBubbleWidthOffset - 23.0f, size.height);
		}
		
		self.textLabel.autoresizingMask = UIViewAutoresizingFlexibleRightMargin;
		bubbleView.autoresizingMask = UIViewAutoresizingFlexibleRightMargin;
		bubbleView.transform = CGAffineTransformIdentity;
		bubbleView.transform = CGAffineTransformMakeScale(-1.0, 1.0);
	}
}

- (void)setImageForBubbleColor:(BubbleColor)color {
	bubbleView.image = [[UIImage imageNamed:[NSString stringWithFormat:@"Bubble-%i.png", color]] stretchableImageWithLeftCapWidth:24 topCapHeight:15];
}

- (void)layoutSubviews {
	[self updateFramesForAuthorType:authorType];
}

#pragma mark -
#pragma mark Setters

- (void)setSelected:(BOOL)selected animated:(BOOL)animated {
    [super setSelected:selected animated:animated];
}

- (void)setAuthorType:(AuthorType)type {
	authorType = type;
	[self updateFramesForAuthorType:authorType];
}

- (void)setBubbleColor:(BubbleColor)color {
	bubbleColor = color;
	[self setImageForBubbleColor:bubbleColor];
}

#pragma mark -
#pragma mark UIGestureRecognizer methods

- (void)longPress:(UILongPressGestureRecognizer *)gestureRecognizer {
	
	if(gestureRecognizer.state == UIGestureRecognizerStateBegan)
	{
		if(canCopyContents)
		{
			UIMenuController *menuController = [UIMenuController sharedMenuController];
			[self becomeFirstResponder];
			[menuController setTargetRect:bubbleView.frame inView:self];
			[menuController setMenuVisible:YES animated:YES];
			
			if(selectionAdjustsColor)
				[self setImageForBubbleColor:selectedBubbleColor];
			
			[[NSNotificationCenter defaultCenter] addObserver:self selector:@selector(willHideMenuController:) name:UIMenuControllerWillHideMenuNotification object:nil];
		}
	}
}

- (void)tap:(UITapGestureRecognizer *)gestureRecognizer {
	if(self.delegate && [(NSObject *)self.delegate respondsToSelector:@selector(tappedImageOfCell:atIndexPath:)])
		[self.delegate tappedImageOfCell:self atIndexPath:[(UITableView *)self.superview indexPathForCell:self]];
}

#pragma mark -
#pragma mark UIMenuController methods

- (BOOL)canPerformAction:(SEL)selector withSender:(id)sender {
    if(selector == @selector(copy:))
		return YES;
	
	return NO;
}

- (BOOL)canBecomeFirstResponder {
    return YES;
}

- (void)copy:(id)sender {
	[[UIPasteboard generalPasteboard] setString:self.textLabel.text];
}

- (void)willHideMenuController:(NSNotification *)notification {
	[self setImageForBubbleColor:bubbleColor];
	[[NSNotificationCenter defaultCenter] removeObserver:self name:UIMenuControllerWillHideMenuNotification object:nil];
}


@end
