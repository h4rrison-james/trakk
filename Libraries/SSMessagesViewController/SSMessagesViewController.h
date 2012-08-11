//
//  SSMessagesViewController.h
//  Messages
//
//  Created by Sam Soffes on 3/10/10.
//  Copyright 2010-2011 Sam Soffes. All rights reserved.
//
//	This is an abstract class for displaying a UI similar to Apple's SMS application. A subclass should override the
//  messageStyleForRowAtIndexPath: and textForRowAtIndexPath: to customize this class.
//

#import "STBubbleTableViewCell.h"
#import "HPGrowingTextView.h"
#import "STMessage.h"

@interface SSMessagesViewController : UIViewController <UITableViewDataSource, UITableViewDelegate, HPGrowingTextViewDelegate, STBubbleTableViewCellDataSource, STBubbleTableViewCellDelegate> {

@private
	
	UITableView *_tableView;
	UIImageView *_inputBackgroundView;
    UIImageView *_textViewBackgroundView;
	HPGrowingTextView *_textView;
	UIButton *_sendButton;
	UIImage *_leftBackgroundImage;
	UIImage *_rightBackgroundImage;
}

@property (nonatomic, retain, readonly) UITableView *tableView;
@property (nonatomic, retain, readonly) UIImageView *inputBackgroundView;
@property (nonatomic, retain, readonly) UIImageView *textViewBackgroundView;
@property (nonatomic, retain, readonly) HPGrowingTextView *textView;
@property (nonatomic, retain, readonly) UIButton *sendButton;
@property (nonatomic, retain) UIImage *leftBackgroundImage;
@property (nonatomic, retain) UIImage *rightBackgroundImage;

- (STMessage *)messageForRowAtIndexPath:(NSIndexPath *)indexPath;
- (void)sendButtonPressed;
- (void)tableViewTapped;
- (void)resizeViewWithOptions:(NSDictionary *)options willHide:(BOOL) willHide;
- (void)scrollToBottomAnimated:(BOOL)animated;

@end
