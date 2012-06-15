//
//  AboutController.m
//  Trakk
//
//  Created by Harrison Sweeney on 6/04/12.
//  Copyright (c) 2012 Harrison J Sweeney. All rights reserved.
//

#import "AboutController.h"

@interface AboutController ()

@end

@implementation AboutController

- (void)viewDidLoad
{
    [super viewDidLoad];
	
    //Set custom background colour
    self.view.backgroundColor = [UIColor colorWithPatternImage:[UIImage imageNamed:@"Background-Pattern"]];
    
    //Set navigation bar shadow
    SET_SHADOW
}

@end
