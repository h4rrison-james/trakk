//
//  STBubbleTableViewCell.h
//  STBubbleTableViewCellDemo
//
//  Created by Cedric Vandendriessche on 18/04/12.
//  Copyright 2011 FreshCreations. All rights reserved.
//

#import <UIKit/UIKit.h>

@class STBubbleTableViewCell;

#define kSTBubbleWidthOffset 30.0f // Extra width added to bubble, should not need to be changed
#define kSTBubbleImageSize 50.0f   // The size of the image

typedef enum {
	STBubbleTableViewCellAuthorTypeSelf = 0,
	STBubbleTableViewCellAuthorTypeOther
} AuthorType;

typedef enum {
	STBubbleTableViewCellBubbleColorGreen = 0,
	STBubbleTableViewCellBubbleColorGray = 1,
	STBubbleTableViewCellBubbleColorAqua = 2, // Standard value of selectedBubbleColor
	STBubbleTableViewCellBubbleColorBrown = 3,
	STBubbleTableViewCellBubbleColorGraphite = 4,
	STBubbleTableViewCellBubbleColorOrange = 5,
	STBubbleTableViewCellBubbleColorPink = 6,
	STBubbleTableViewCellBubbleColorPurple = 7,
    STBubbleTableViewCellBubbleColorRed = 8,
	STBubbleTableViewCellBubbleColorYellow = 9
} BubbleColor;

@protocol STBubbleTableViewCellDataSource
@optional
- (CGFloat)minInsetForCell:(STBubbleTableViewCell *)cell atIndexPath:(NSIndexPath *)indexPath;
@end

@protocol STBubbleTableViewCellDelegate
@optional
- (void)tappedImageOfCell:(STBubbleTableViewCell *)cell atIndexPath:(NSIndexPath *)indexPath;
@end

@interface STBubbleTableViewCell : UITableViewCell {
	UIImageView *bubbleView;
	AuthorType authorType;
	BubbleColor bubbleColor;
	BubbleColor selectedBubbleColor;
	BOOL canCopyContentsOfCell; // Defaults to YES
	BOOL selectionAdjustsColor; // Defaults to YES
	id <STBubbleTableViewCellDataSource> __unsafe_unretained dataSource;
	id <STBubbleTableViewCellDelegate> __unsafe_unretained delegate;
}

@property (nonatomic, strong, readonly) UIImageView *bubbleView;
@property (nonatomic, readwrite) AuthorType authorType;
@property (nonatomic, readwrite) BubbleColor bubbleColor;
@property (nonatomic, readwrite) BubbleColor selectedBubbleColor;
@property (nonatomic, readwrite) BOOL canCopyContents;
@property (nonatomic, readwrite) BOOL selectionAdjustsColor;
@property (nonatomic, unsafe_unretained) id <STBubbleTableViewCellDataSource> dataSource;
@property (nonatomic, unsafe_unretained) id <STBubbleTableViewCellDelegate> delegate;

@end
