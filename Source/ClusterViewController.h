//
//  ClusterViewController.h
//  Trakk
//
//  Created by Harrison Sweeney on 11/07/12.
//  Copyright (c) 2012 UWA. All rights reserved.
//

#import <UIKit/UIKit.h>
#import "userAnnotation.h"
#import "ClusterViewCell.h"

@interface ClusterViewController : UITableViewController {
    NSArray *annotations;
}

@property (nonatomic, retain) NSArray* annotations;

@end
