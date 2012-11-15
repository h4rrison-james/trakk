//
//  FriendViewCellController.m
//  Trakk
//
//  Created by Harrison Sweeney on 22/07/11.
//  Copyright 2011 Harrison J Sweeney. All rights reserved.
//

#import "FriendViewCellController.h"

@implementation FriendViewCellController

@synthesize nameLabel;
@synthesize profileImage;
@synthesize request = _request;
@synthesize pictureData;

// Called every time a chunk of the data is received
- (void)connection:(NSURLConnection *)connection didReceiveData:(NSData *)data {
    [pictureData appendData:data]; // Build the image
}

// Called when the entire image is finished downloading
- (void)connectionDidFinishLoading:(NSURLConnection *)connection {
    // Set the image in the header imageView
    profileImage.image = [UIImage imageWithData:pictureData];
}

- (id)initWithStyle:(UITableViewCellStyle)style reuseIdentifier:(NSString *)reuseIdentifier
{
    self = [super initWithStyle:style reuseIdentifier:reuseIdentifier];
    if (self) {
        // Initialization code
    }
    return self;
}

- (void)setSelected:(BOOL)selected animated:(BOOL)animated
{
    [super setSelected:selected animated:animated];

    // Configure the view for the selected state
}

- (void)prepareForReuse
{ //If there is a pending Facebook request that has not yet been recieved, cancel it
    if (_request)
        [[_request connection] cancel];
}

- (void)dealloc
{ //Also remove request when deallocating to avoid crash
    if (_request)
        [[_request connection] cancel];
}

@end
