//
//  STBubbleTableViewCellDemoViewController.m
//  STBubbleTableViewCellDemo
//
//  Created by Cedric Vandendriessche on 18/04/12.
//  Copyright 2011 FreshCreations. All rights reserved.
//

#import "STBubbleTableViewCellDemoViewController.h"
#import "STBubbleTableViewCell.h"
#import "Message.h"

@implementation STBubbleTableViewCellDemoViewController

@synthesize tbl, messages;

- (void)viewDidLoad {
    [super viewDidLoad];
	self.title = @"Messages";
	
//	messages = [[NSMutableArray alloc] initWithObjects:
//				[Message messageWithString:@"How is that bubble component of yours coming along?" image:[UIImage imageNamed:@"jonnotie.png"]],
//				[Message messageWithString:@"Great, I just finished avatar support." image:[UIImage imageNamed:@"SkyTrix.png"]],
//				[Message messageWithString:@"That is awesome! I hope people will like that addition." image:[UIImage imageNamed:@"jonnotie.png"]],
//				[Message messageWithString:@"They will. Now you see me.." image:[UIImage imageNamed:@"SkyTrix.png"]],
//				[Message messageWithString:@"And now you don't. :)"],
//				nil];
	
	tbl.backgroundColor = [UIColor colorWithRed:219.0/255.0 green:226.0/255.0 blue:237.0/255.0 alpha:1.0];
	tbl.separatorStyle = UITableViewCellSeparatorStyleNone;
	
	// Some decoration
	CGSize screenSize = [[UIScreen mainScreen] applicationFrame].size;	
	UIView *headerView = [[UIView alloc] initWithFrame:CGRectMake(0, 0, screenSize.width, 55)];
	
	UIButton *callButton = [UIButton buttonWithType:UIButtonTypeRoundedRect];
	callButton.frame = CGRectMake(10, 10, (screenSize.width / 2) - 10, 35);
	callButton.autoresizingMask = UIViewAutoresizingFlexibleWidth | UIViewAutoresizingFlexibleRightMargin;
	[callButton setTitle:@"Call" forState:UIControlStateNormal];
	
	UIButton *contactButton = [UIButton buttonWithType:UIButtonTypeRoundedRect];
	contactButton.frame = CGRectMake((screenSize.width / 2) + 10, 10, (screenSize.width / 2) - 20, 35);
	contactButton.autoresizingMask = UIViewAutoresizingFlexibleWidth | UIViewAutoresizingFlexibleLeftMargin;
	[contactButton setTitle:@"Contact Info" forState:UIControlStateNormal];
	
	[headerView addSubview:callButton];
	[headerView addSubview:contactButton];
	
	tbl.tableHeaderView = headerView;
}

#pragma mark -
#pragma mark Table view methods

- (NSInteger)numberOfSectionsInTableView:(UITableView *)tableView {
    return 1;
}

- (NSInteger)tableView:(UITableView *)tableView numberOfRowsInSection:(NSInteger)section {
    return [messages count];
}

- (UITableViewCell *)tableView:(UITableView *)tableView cellForRowAtIndexPath:(NSIndexPath *)indexPath {
    
    static NSString *CellIdentifier = @"Cell";
	
    STBubbleTableViewCell *cell = (STBubbleTableViewCell *)[tableView dequeueReusableCellWithIdentifier:CellIdentifier];
    if (cell == nil) {
        cell = [[STBubbleTableViewCell alloc] initWithStyle:UITableViewCellStyleDefault reuseIdentifier:CellIdentifier];
		cell.selectionStyle = UITableViewCellSelectionStyleNone;
		
		cell.dataSource = self;
		cell.delegate = self;
	}
	
	Message *message = [messages objectAtIndex:indexPath.row];
	
	cell.textLabel.font = [UIFont systemFontOfSize:14.0];
	cell.textLabel.text = message.message;
	cell.imageView.image = message.avatar;
	
	if(indexPath.row % 2 != 0 || indexPath.row == 4)
	{
		cell.authorType = STBubbleTableViewCellAuthorTypeSelf;
		cell.bubbleColor = STBubbleTableViewCellBubbleColorGreen;
	}
	else
	{
		cell.authorType = STBubbleTableViewCellAuthorTypeOther;
		cell.bubbleColor = STBubbleTableViewCellBubbleColorGray;
	}
		
    return cell;
}

- (CGFloat)tableView:(UITableView *)tableView heightForRowAtIndexPath:(NSIndexPath *)indexPath {
	Message *message = [messages objectAtIndex:indexPath.row];
	
	CGSize size;
	
	if(message.avatar)
		size = [message.message sizeWithFont:[UIFont systemFontOfSize:14.0] constrainedToSize:CGSizeMake(tbl.frame.size.width - [self minInsetForCell:nil atIndexPath:indexPath] - kSTBubbleImageSize - 8.0f - kSTBubbleWidthOffset, 480.0) lineBreakMode:UILineBreakModeWordWrap];
	else
		size = [message.message sizeWithFont:[UIFont systemFontOfSize:14.0] constrainedToSize:CGSizeMake(tbl.frame.size.width - [self minInsetForCell:nil atIndexPath:indexPath] - kSTBubbleWidthOffset, 480.0) lineBreakMode:UILineBreakModeWordWrap];
	
	// This makes sure the cell is big enough to hold the avatar
	if(size.height + 15.0f < kSTBubbleImageSize + 4.0f && message.avatar)
		return kSTBubbleImageSize + 4.0f;
	
	return size.height + 15.0f;
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
	Message *message = [messages objectAtIndex:indexPath.row];
	NSLog(@"%@", message.message);
}

#pragma mark -

- (BOOL)shouldAutorotateToInterfaceOrientation:(UIInterfaceOrientation)interfaceOrientation {
	return (interfaceOrientation != UIInterfaceOrientationPortraitUpsideDown);
}

- (void)willAnimateRotationToInterfaceOrientation:(UIInterfaceOrientation)interfaceOrientation duration:(NSTimeInterval)duration {
	[tbl reloadData];
}

- (void)didReceiveMemoryWarning {
	// Releases the view if it doesn't have a superview.
    [super didReceiveMemoryWarning];
	
	// Release any cached data, images, etc that aren't in use.
}

- (void)viewDidUnload {
	self.tbl = nil;
}


@end
