//
//  SettingsController.m
//  Trakk
//
//  Created by Harrison Sweeney on 31/03/12.
//  Copyright (c) 2012 Harrison J Sweeney. All rights reserved.
//

#import "SettingsController.h"
#import "QuartzCore/QuartzCore.h"
#import "Constants.h"

@interface SettingsController ()

@end

@implementation SettingsController
@synthesize peopleSwitch;
@synthesize clubSwitch;
@synthesize versionLabel;
@synthesize updateLabel;

- (void)viewDidLoad
{
    [super viewDidLoad];
    
    //Add shadow to navigation bar
    SET_SHADOW
    
    //Set version label to current version
    NSDictionary* infoDict = [[NSBundle mainBundle] infoDictionary];
    NSString* versionNum = [infoDict objectForKey:@"CFBundleVersion"];
    versionLabel.text = versionNum;
    
    //Set people switch
    if (0)
    {
        NSUserDefaults *defaults = [NSUserDefaults standardUserDefaults];
        if ([defaults objectForKey:@"showPeopleOnMap"])
        {
            if ([[defaults objectForKey:@"showPeopleOnMap"] isEqualToString:@"1"])
                peopleSwitch.on = TRUE;
            else 
                peopleSwitch.on = FALSE;
        }
        else 
            peopleSwitch.on = TRUE;
        
        //Set club switch
        if ([defaults objectForKey:@"showClubsOnMap"])
        {
            if ([[defaults objectForKey:@"showClubsOnMap"] isEqualToString:@"1"])
                clubSwitch.on = TRUE;
            else 
                clubSwitch.on = FALSE;
        }
        else 
            clubSwitch.on = TRUE;
    }
}

- (void)viewDidUnload
{
    [self setVersionLabel:nil];
    [self setUpdateLabel:nil];
    [self setPeopleSwitch:nil];
    [self setClubSwitch:nil];
    [super viewDidUnload];
    // Release any retained subviews of the main view.
    // e.g. self.myOutlet = nil;
}

-(void)viewWillAppear:(BOOL)animated
{
    NSUserDefaults *defaults = [NSUserDefaults standardUserDefaults];
    if ([defaults objectForKey:@"updateInterval"])
    {
        int seconds = [[defaults objectForKey:@"updateInterval"] intValue];
        int minutes = seconds / 60;
        NSString *labelText = [NSString stringWithFormat:@"%d minutes", minutes];
        updateLabel.text = labelText;
    }
    else
    {
        updateLabel.text = @"10 minutes";
    }
}

-(void)viewDidAppear:(BOOL)animated
{
    [self.tableView deselectRowAtIndexPath:[self.tableView indexPathForSelectedRow] animated:YES];
}

#pragma mark - Table view delegate

- (void)tableView:(UITableView *)tableView didSelectRowAtIndexPath:(NSIndexPath *)indexPath
{
    if ([indexPath section] == 1 && [indexPath row] == 0)
    { //Feedback cell selected
        if ([MFMailComposeViewController canSendMail])
        { //Check if device has ability to send mail
            MFMailComposeViewController *mailer = [[MFMailComposeViewController alloc] init];
            mailer.mailComposeDelegate = self;
            [mailer setSubject:@"Feedback"];
            [mailer setToRecipients:[NSArray arrayWithObject:@"feedback@trakkapp.com"]];
            [self presentModalViewController:mailer animated:YES];
        }
        else
        { //Display error alert
            UIAlertView *mailAlert = [[UIAlertView alloc] initWithTitle:@"Mail Unavailable" message:@"This device does not have the ability to send mail. Please see our website to sumbit feedback." delegate:self cancelButtonTitle:nil otherButtonTitles:nil];
            [mailAlert show];
        }
    }
    else if ([indexPath section] == 1 && [indexPath row] == 1)
    { //App Store rating cell selected
        [[UIApplication sharedApplication] openURL:[NSURL URLWithString:@"itms-apps://ax.itunes.apple.com/WebObjects/MZStore.woa/wa/viewContentsUserReviews?type=Purple+Software&id=510960845"]];
    }
}

- (CGFloat)tableView:(UITableView *)tableView
heightForFooterInSection:(NSInteger)section
{
    return 50;
}

- (UIView *)tableView:(UITableView *)tableView viewForFooterInSection:(NSInteger)section {
    
    if(footerView == nil && section == 1) {
        //Allocate the view if it doesn't exist yet
        footerView  = [[UIView alloc] init];
        
        //We would like to show a glossy red button, so get the image first
        UIImage *image = [[UIImage imageNamed:@"Logout-Button.png"]
                          stretchableImageWithLeftCapWidth:8 topCapHeight:8];
        
        //Create the button
        UIButton *button = [UIButton buttonWithType:UIButtonTypeRoundedRect];
        [button setBackgroundImage:image forState:UIControlStateNormal];
        
        //The button should be as big as a table view cell
        [button setFrame:CGRectMake(10, 10, 300, 44)];
        
        //Set title, font size, shadow, and font color
        [button setTitle:@"Logout" forState:UIControlStateNormal];
        [button.titleLabel setFont:[UIFont boldSystemFontOfSize:20]];
        [button setTitleColor:[UIColor whiteColor] forState:UIControlStateNormal];
        
        //Set action of the button
        [button addTarget:self action:@selector(logoutButtonPressed)
         forControlEvents:UIControlEventTouchUpInside];
        
        //Add the button to the view
        [footerView addSubview:button];
    }
    
    //return the view for the footer
    return footerView;
}

- (IBAction)cancelButtonPressed:(id)sender
{
    [self.presentingViewController dismissModalViewControllerAnimated:YES];
}

- (IBAction)peopleSwitchChanged:(id)sender forEvent:(UIEvent *)event
{
    NSUserDefaults *defaults = [NSUserDefaults standardUserDefaults];
    if (peopleSwitch.on)
        [defaults setObject:@"1" forKey:@"showPeopleOnMap"];
    else
        [defaults setObject:@"0" forKey:@"showPeopleOnMap"];
}

- (IBAction)clubSwitchChanged:(id)sender forEvent:(UIEvent *)event
{
    NSUserDefaults *defaults = [NSUserDefaults standardUserDefaults];
    if (clubSwitch.on)
        [defaults setObject:@"1" forKey:@"showClubsOnMap"];
    else
        [defaults setObject:@"0" forKey:@"showClubsOnMap"];
}

- (void)logoutButtonPressed
{
    //Log user out
    [PFUser logOut];
    
    //Present launch view controller
    [self performSegueWithIdentifier:@"logout" sender:self];
}

# pragma mark - Mail Composer Results

- (void)mailComposeController:(MFMailComposeViewController *)controller didFinishWithResult:(MFMailComposeResult)result error:(NSError *)error {
    
    if (MFMailComposeResultSent) {
        DLog(@"Sent!");
    }
    
    if (MFMailComposeResultSaved) {
        DLog(@"Mail Saved");
    }
    
    if (MFMailComposeResultCancelled) {
        DLog(@"User Cancelled");
    }
    
    if (MFMailComposeResultFailed) {
        DLog(@"Send Failed");
    }
    
    [self dismissModalViewControllerAnimated:YES];
}

@end
