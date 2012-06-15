//
//  main.m
//  Trakk
//
//  Created by Harrison Sweeney on 24/06/11.
//  Copyright 2011 Harrison J Sweeney. All rights reserved.
//

#import <UIKit/UIKit.h>

#import "utrakAppDelegate.h"
#import "Parse/Parse.h"

int main(int argc, char *argv[])
{
    [Parse setApplicationId:@"p3nmQh5hWuhDeem4JYoqZeCOHuW4ytcsGbQz93tw" 
                  clientKey:@"WkbnKAso9YF06XsWs8H8Azbzftr477zCIdZYFgye"];
    
    int retVal = 0;
    @autoreleasepool {
        retVal = UIApplicationMain(argc, argv, nil, NSStringFromClass([utrakAppDelegate class]));
    }
    return retVal;
}
