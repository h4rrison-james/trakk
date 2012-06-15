//
//  Constants.h
//  Trakk
//
//  Created by Harrison Sweeney on 10/03/12.
//  Copyright (c) 2012 Harrison J Sweeney. All rights reserved.
//

#ifndef Trakk_Constants_h
#define Trakk_Constants_h

//Standard update interval for location controller in seconds
#define UPDATE_INTERVAL 600;

#define SET_SHADOW self.navigationController.navigationBar.layer.shadowColor = [[UIColor blackColor] CGColor];\
        self.navigationController.navigationBar.layer.shadowOffset = CGSizeMake(0.0, 0.5);\
        self.navigationController.navigationBar.layer.masksToBounds = NO;\
        self.navigationController.navigationBar.layer.shouldRasterize = YES;\
        self.navigationController.navigationBar.layer.shadowOpacity = 1;\

#endif
