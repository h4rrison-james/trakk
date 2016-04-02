//
//  ViewController.m
//  Trakk Wifi
//
//  Created by Harrison Sweeney on 10/03/12.
//  Copyright (c) 2012 UWA. All rights reserved.
//

#import "ViewController.h"
#import <SystemConfiguration/CaptiveNetwork.h>

@implementation ViewController
@synthesize textView;
@synthesize nameLabel;
@synthesize bssidLabel;
@synthesize locationField;

- (void)didReceiveMemoryWarning
{
    [super didReceiveMemoryWarning];
    // Release any cached data, images, etc that aren't in use.
}

#pragma mark - View lifecycle

- (void)viewDidLoad
{
    [super viewDidLoad];
	NSUserDefaults *defaults = [NSUserDefaults standardUserDefaults];
    if ([defaults objectForKey:@"text"])
    {
        textView.text = [defaults objectForKey:@"text"];
    }
}

- (void)viewDidUnload
{
    NSUserDefaults *defaults = [NSUserDefaults standardUserDefaults];
    [defaults setObject:textView.text forKey:@"text"];
    [self setNameLabel:nil];
    [self setBssidLabel:nil];
    [self setLocationField:nil];
    [self setTextView:nil];
    [super viewDidUnload];
    // Release any retained subviews of the main view.
    // e.g. self.myOutlet = nil;
}

- (void)viewWillAppear:(BOOL)animated
{
    [super viewWillAppear:animated];
}

- (void)viewDidAppear:(BOOL)animated
{
    [super viewDidAppear:animated];
}

- (void)viewWillDisappear:(BOOL)animated
{
	[super viewWillDisappear:animated];
}

- (void)viewDidDisappear:(BOOL)animated
{
	[super viewDidDisappear:animated];
}

- (BOOL)shouldAutorotateToInterfaceOrientation:(UIInterfaceOrientation)interfaceOrientation
{
    // Return YES for supported orientations
    if ([[UIDevice currentDevice] userInterfaceIdiom] == UIUserInterfaceIdiomPhone) {
        return (interfaceOrientation != UIInterfaceOrientationPortraitUpsideDown);
    } else {
        return YES;
    }
}

- (NSDictionary *)fetchSSIDInfo
{
    NSArray *ifs = (__bridge_transfer id)CNCopySupportedInterfaces();
    NSDictionary* info = nil;
    for (NSString *ifnam in ifs)
    {
        info = (__bridge_transfer id)CNCopyCurrentNetworkInfo((__bridge_retained CFStringRef)ifnam);
        NSLog(@"Network SSID: %@", [info objectForKey:@"SSID"]);
        NSLog(@"Network BSSID: %@", [info objectForKey:@"BSSID"]);
    }
    return info;
}

- (IBAction)savePressed:(id)sender {
    NSString *text = locationField.text;
    NSString *string = [NSString stringWithFormat:@"Location: %@\nBSSID: %@\n", text, bssidLabel.text];
    locationField.text = @"";
    [textView.text stringByAppendingString:string];
    [textView reloadInputViews];
}

- (IBAction)refreshPressed:(id)sender {
    NSDictionary *info = [self fetchSSIDInfo];
    nameLabel.text = [info objectForKey:@"SSID"];
    if ([info objectForKey:@"BSSID"] && [[info objectForKey:@"BSSID"] isKindOfClass:[NSString class]])
        bssidLabel.text = [info objectForKey:@"BSSID"];
}
@end
