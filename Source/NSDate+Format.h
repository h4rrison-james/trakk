//
//  NSDate+Format.h
//  Trakk
//
//  Created by Harrison Sweeney on 21/07/12.
//  Copyright (c) 2012 UWA. All rights reserved.
//

#import <Foundation/Foundation.h>

#define TT_MINUTE 60
#define TT_HOUR   (60 * TT_MINUTE)
#define TT_DAY    (24 * TT_HOUR)
#define TT_5_DAYS (5 * TT_DAY)
#define TT_WEEK   (7 * TT_DAY)
#define TT_MONTH  (30.5 * TT_DAY)
#define TT_YEAR   (365 * TT_DAY)

@interface NSDate (Format)

/**
 * Returns a copy of the date with the time set to midnight on the same day.
 */
- (NSDate*)dateAtMidnight;

/**
 * Formats the date with 'h:mm a' or the localized equivalent.
 */
- (NSString*)formatTime;

/**
 * Formats the date with 'EEEE, LLLL d, YYYY' or the localized equivalent.
 */
- (NSString*)formatDate;

/**
 * Formats the date according to how old it is.
 *
 * For dates less than a day old, the format is 'h:mm a', for less than a week old the
 * format is 'EEEE', and for anything older the format is 'M/d/yy'.
 */
- (NSString*)formatShortTime;

/**
 * Formats the date according to how old it is.
 *
 * For dates less than a day old, the format is 'h:mm a', for less than a week old the
 * format is 'EEE h:mm a', and for anything older the format is 'MMM d h:mm a'.
 */
- (NSString*)formatDateTime;

/**
 * Formats dates within 24 hours like '5 minutes ago', or calls formatDateTime if older.
 */
- (NSString*)formatRelativeTime;

/**
 * Formats dates within 1 week like '5m' or '2d', or calls formatShortTime if older.
 */
- (NSString*)formatShortRelativeTime;

/**
 * Formats the date with 'MMMM d", "Today", or "Yesterday".
 *
 * You must supply date components for today and yesterday because they are relatively expensive
 * to create, so it is best to avoid creating them every time you call this method if you
 * are going to be calling it multiple times in a loop.
 */
- (NSString*)formatDay:(NSDateComponents*)today yesterday:(NSDateComponents*)yesterday;

/**
 * Formats the date with 'MMMM".
 */
- (NSString*)formatMonth;

/**
 * Formats the date with 'yyyy".
 */
- (NSString*)formatYear;

@end
