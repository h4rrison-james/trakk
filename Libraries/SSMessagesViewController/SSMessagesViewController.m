//
//  SSMessagesViewController.m
//  Messages
//
//  Created by Sam Soffes on 3/10/10.
//  Copyright 2010-2011 Sam Soffes. All rights reserved.
//

#import "SSMessagesViewController.h"
#import "SSMessageTableViewCell.h"
#import "SSMessageTableViewCellBubbleView.h"

static CGFloat const kInputHeight = 40.0f;
static CGFloat const kMessageFontSize   = 16.0f;
static CGFloat const kContentHeightMax  = 84.0f;
static CGFloat const kChatBarHeight1    = 40.0f;
static CGFloat const kChatBarHeight4    = 94.0f;
CGFloat previousContentHeight;

@implementation SSMessagesViewController

@synthesize tableView = _tableView;
@synthesize inputBackgroundView = _inputBackgroundView;
@synthesize textViewBackgroundView = _textViewBackgroundView;
@synthesize textView = _textView;
@synthesize sendButton = _sendButton;
@synthesize leftBackgroundImage = _leftBackgroundImage;
@synthesize rightBackgroundImage = _rightBackgroundImage;

#pragma mark NSObject

- (void)dealloc {
	self.leftBackgroundImage = nil;
	self.rightBackgroundImage = nil;
	[_tableView release];
	[_inputBackgroundView release];
	[_textView release];
	[_sendButton release];
	[super dealloc];
}


#pragma mark UIViewController

- (void)viewDidLoad {
	self.view.backgroundColor = [UIColor colorWithRed:0.859f green:0.886f blue:0.929f alpha:1.0f];
    
    // Listen for keyboard notifications
    [[NSNotificationCenter defaultCenter] addObserver:self selector:@selector(keyboardWillShow:)
                                                 name:UIKeyboardWillShowNotification object:nil];
    [[NSNotificationCenter defaultCenter] addObserver:self selector:@selector(keyboardWillHide:)
                                                 name:UIKeyboardWillHideNotification object:nil];
	
	CGSize size = self.view.frame.size;
	
	// Table view
	_tableView = [[UITableView alloc] initWithFrame:CGRectMake(0.0f, 0.0f, size.width, size.height - kInputHeight) style:UITableViewStylePlain];
	_tableView.autoresizingMask = UIViewAutoresizingFlexibleWidth | UIViewAutoresizingFlexibleHeight;
	_tableView.backgroundColor = self.view.backgroundColor;
	_tableView.dataSource = self;
	_tableView.delegate = self;
	_tableView.separatorColor = self.view.backgroundColor;
    
    UITapGestureRecognizer *tapTable = [[UITapGestureRecognizer alloc] initWithTarget:self action:@selector(tableViewTapped)];
    [_tableView addGestureRecognizer:tapTable];
	[self.view addSubview:_tableView];
	
	// Input
	_inputBackgroundView = [[UIImageView alloc] initWithFrame:CGRectMake(0.0f, size.height - kInputHeight, size.width, kInputHeight)];
	_inputBackgroundView.autoresizingMask = UIViewAutoresizingFlexibleWidth | UIViewAutoresizingFlexibleTopMargin;
    UIImage *rawBackgroundView = [UIImage imageNamed:@"MessageEntryBackground.png"];
	_inputBackgroundView.image = [rawBackgroundView stretchableImageWithLeftCapWidth:13 topCapHeight:22];
	_inputBackgroundView.userInteractionEnabled = YES;
	[self.view addSubview:_inputBackgroundView];
	
	// Text view
    _textView = [[HPGrowingTextView alloc] initWithFrame:CGRectMake(6, 3, 240, 40)];
    _textView.contentInset = UIEdgeInsetsMake(0, 5, 0, 5);
	_textView.minNumberOfLines = 1;
	_textView.maxNumberOfLines = 4;
	_textView.returnKeyType = UIReturnKeyGo;
	_textView.font = [UIFont systemFontOfSize:15.0f];
	_textView.delegate = self;
    _textView.internalTextView.scrollIndicatorInsets = UIEdgeInsetsMake(5, 0, 5, 0);
    _textView.autoresizingMask = UIViewAutoresizingFlexibleWidth;
    _textView.backgroundColor = [UIColor whiteColor];
	[_inputBackgroundView addSubview:_textView];
    
    // Text View Background Image
    UIImage *rawEntryBackground = [UIImage imageNamed:@"MessageEntryInputField.png"];
    UIImage *entryBackground = [rawEntryBackground stretchableImageWithLeftCapWidth:13 topCapHeight:22];
    _textViewBackgroundView = [[[UIImageView alloc] initWithImage:entryBackground] autorelease];
    _textViewBackgroundView.frame = CGRectMake(5, 0, 248, 40);
    _textViewBackgroundView.autoresizingMask = UIViewAutoresizingFlexibleHeight | UIViewAutoresizingFlexibleWidth;
    [_inputBackgroundView addSubview:_textViewBackgroundView];
	
	// Send button
	_sendButton = [[UIButton buttonWithType:UIButtonTypeCustom] retain];
	_sendButton.frame = CGRectMake(size.width - 65.0f, 8.0f, 59.0f, 27.0f);
	_sendButton.autoresizingMask = UIViewAutoresizingFlexibleLeftMargin | UIViewAutoresizingFlexibleTopMargin;
	_sendButton.titleLabel.font = [UIFont boldSystemFontOfSize:16.0f];
	_sendButton.titleLabel.shadowOffset = CGSizeMake(0.0f, -1.0f);
	[_sendButton setBackgroundImage:[[UIImage imageNamed:@"SSMessagesViewControllerSendButtonBackground.png"] stretchableImageWithLeftCapWidth:12 topCapHeight:0] forState:UIControlStateNormal];
	[_sendButton setTitle:@"Send" forState:UIControlStateNormal];
	[_sendButton setTitleColor:[UIColor colorWithWhite:1.0f alpha:0.4f] forState:UIControlStateNormal];
	[_sendButton setTitleShadowColor:[UIColor colorWithRed:0.325f green:0.463f blue:0.675f alpha:1.0f] forState:UIControlStateNormal];
    [_sendButton addTarget:self action:@selector(sendButtonPressed) forControlEvents:UIControlEventTouchDown];
	[_inputBackgroundView addSubview:_sendButton];
}

- (void)viewDidUnload {
    // Unregister for keyboard notifications
    [[NSNotificationCenter defaultCenter] removeObserver:self];
}

#pragma mark SSMessagesViewController

//This method is intended to be overridden by subclasses
- (STMessage *)messageForRowAtIndexPath:(NSIndexPath *)indexPath {
    return nil;
}

//This method is intended to be overridden by subclasses
- (void)sendButtonPressed {
    NSLog(@"Send Button Pressed");
}

//This method is intended to be overridden by subclasses
- (void)scrollToBottomAnimated:(BOOL)animated {
    //Do nothing
}

#pragma mark Keyboard Notifications

- (void)keyboardWillShow:(NSNotification *)notification {
    [self resizeViewWithOptions:[notification userInfo] willHide:FALSE];
}

- (void)keyboardWillHide:(NSNotification *)notification {
    [self resizeViewWithOptions:[notification userInfo] willHide:TRUE];
}

- (void)resizeViewWithOptions:(NSDictionary *)options willHide:(BOOL) willHide {
    NSTimeInterval animationDuration;
    UIViewAnimationCurve animationCurve;
    CGRect keyboardEndFrame;
    [[options objectForKey:UIKeyboardAnimationCurveUserInfoKey] getValue:&animationCurve];
    [[options objectForKey:UIKeyboardAnimationDurationUserInfoKey] getValue:&animationDuration];
    [[options objectForKey:UIKeyboardFrameEndUserInfoKey] getValue:&keyboardEndFrame];
    
    [UIView animateWithDuration:animationDuration delay:0 options:0 animations:^{
        
        //Save current table view frame and input view frame
        CGRect tableFrame = _tableView.frame;
        CGRect inputFrame = _inputBackgroundView.frame;
        CGRect keyboardFrameEndRelative = [self.view convertRect:keyboardEndFrame fromView:nil];
        
        //Move both the table view and input view up together
        tableFrame.origin.y = keyboardFrameEndRelative.origin.y - tableFrame.size.height - inputFrame.size.height;
        inputFrame.origin.y = keyboardFrameEndRelative.origin.y - inputFrame.size.height;
        
        _tableView.frame = tableFrame;
        _inputBackgroundView.frame = inputFrame;
        
        //Set the content inset so the top of the table displays correctly
        if (willHide)
            _tableView.contentInset = UIEdgeInsetsMake(0, 0, 0, 0);
        else
            _tableView.contentInset = UIEdgeInsetsMake(keyboardFrameEndRelative.size.height, 0, 0, 0);
        
        [_sendButton setTitleColor:[UIColor whiteColor] forState:UIControlStateNormal];
    } completion:^(BOOL finished) {
        //Animation finished
    }];
}

#pragma mark HPGrowingTextView

- (void)growingTextView:(HPGrowingTextView *)growingTextView willChangeHeight:(float)height
{
    float diff = (growingTextView.frame.size.height - height);
    
	CGRect r = _inputBackgroundView.frame;
    r.size.height -= diff;
    r.origin.y += diff;
	_inputBackgroundView.frame = r;
}

#pragma mark UITableViewDataSource

- (NSInteger)numberOfSectionsInTableView:(UITableView *)tableView {
    return 1;
}


- (NSInteger)tableView:(UITableView *)tableView numberOfRowsInSection:(NSInteger)section {
    return 0;
}

- (UITableViewCell *)tableView:(UITableView *)tableView cellForRowAtIndexPath:(NSIndexPath *)indexPath {
    
    static NSString *CellIdentifier = @"cellIdentifier";
	
    STBubbleTableViewCell *cell = (STBubbleTableViewCell *)[tableView dequeueReusableCellWithIdentifier:CellIdentifier];
    if (cell == nil) {
        cell = [[STBubbleTableViewCell alloc] initWithStyle:UITableViewCellStyleDefault reuseIdentifier:CellIdentifier];
		cell.selectionStyle = UITableViewCellSelectionStyleNone;
		
		cell.dataSource = self;
		cell.delegate = self;
	}
	
	STMessage *message = [self messageForRowAtIndexPath:indexPath];
	
	cell.textLabel.text = message.message;
	cell.imageView.image = message.avatar;
    cell.authorType = message.author;
    
    if (cell.authorType == STBubbleTableViewCellAuthorTypeSelf)
        cell.bubbleColor = STBubbleTableViewCellBubbleColorGreen;
    if (cell.authorType == STBubbleTableViewCellAuthorTypeOther)
        cell.bubbleColor = STBubbleTableViewCellBubbleColorGray;
    
    return cell;
}

#pragma mark -
#pragma mark STBubbleTableViewCellDataSource methods

- (CGFloat)minInsetForCell:(STBubbleTableViewCell *)cell atIndexPath:(NSIndexPath *)indexPath {
	if(self.interfaceOrientation == UIInterfaceOrientationLandscapeLeft || self.interfaceOrientation == UIInterfaceOrientationLandscapeRight)
		return 100;
	return 50;
}

#pragma mark -
#pragma mark STBubbleTableViewCellDelegate methods

- (void)tappedImageOfCell:(STBubbleTableViewCell *)cell atIndexPath:(NSIndexPath *)indexPath {
	STMessage *message = [self messageForRowAtIndexPath:indexPath];
	DLog(@"%@", message.message);
}

#pragma mark UITableViewDelegate

- (CGFloat)tableView:(UITableView *)tableView heightForRowAtIndexPath:(NSIndexPath *)indexPath {
	STMessage *message = [self messageForRowAtIndexPath:indexPath];
	
	CGSize size;
	
	if(message.avatar)
		size = [message.message sizeWithFont:[UIFont systemFontOfSize:14.0] constrainedToSize:CGSizeMake(tableView.frame.size.width - [self minInsetForCell:nil atIndexPath:indexPath] - kSTBubbleImageSize - 8.0f - kSTBubbleWidthOffset, 480.0) lineBreakMode:UILineBreakModeWordWrap];
	else
		size = [message.message sizeWithFont:[UIFont systemFontOfSize:14.0] constrainedToSize:CGSizeMake(tableView.frame.size.width - [self minInsetForCell:nil atIndexPath:indexPath] - kSTBubbleWidthOffset, 480.0) lineBreakMode:UILineBreakModeWordWrap];
	
	// This makes sure the cell is big enough to hold the avatar
	if(size.height + 15.0f < kSTBubbleImageSize + 4.0f && message.avatar)
		return kSTBubbleImageSize + 4.0f;
	
	return size.height + 15.0f;
}

#pragma mark UIGestureRecognizer

- (void)tableViewTapped
{ //Dismiss the keyboard by resigning first responder
    [_textView resignFirstResponder];
}

@end
