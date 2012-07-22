//
//  NSDate+Format.m
//  Trakk
//
//  Created by Harrison Sweeney on 21/07/12.
//  Copyright (c) 2012 UWA. All rights reserved.
//

#import "NSDate+Format.h"

@implementation NSDate (Format)

- (NSDate*)dateAtMidnight {
	NSCalendar *gregorian = [[NSCalendar alloc] initWithCalendarIdentifier:NSGregorianCalendar];
	NSDateComponents *comps = [gregorian components:
                               (NSYearCalendarUnit|NSMonthCalendarUnit|NSDayCalendarUnit)
                                           fromDate:[NSDate date]];
	NSDate *midnight = [gregorian dateFromComponents:comps];
	return midnight;
}


- (NSString*)formatTime {
    static NSDateFormatter* formatter = nil;
    if (nil == formatter) {
        formatter = [[NSDateFormatter alloc] init];
        formatter.dateFormat = @"h:mm a";
    }
    return [formatter stringFromDate:self];
}


- (NSString*)formatDate {
    static NSDateFormatter* formatter = nil;
    if (nil == formatter) {
        formatter = [[NSDateFormatter alloc] init];
        formatter.dateFormat = @"EEEE, LLLL d, YYYY";
    }
    return [formatter stringFromDate:self];
}


- (NSString*)formatShortTime {
    NSTimeInterval diff = abs([self timeIntervalSinceNow]);
    
    if (diff < TT_DAY) {
        return [self formatTime];
        
    } else if (diff < TT_5_DAYS) {
        static NSDateFormatter* formatter = nil;
        if (nil == formatter) {
            formatter = [[NSDateFormatter alloc] init];
            formatter.dateFormat = @"EEEE";
        }
        return [formatter stringFromDate:self];
        
    } else {
        static NSDateFormatter* formatter = nil;
        if (nil == formatter) {
            formatter = [[NSDateFormatter alloc] init];
            formatter.dateFormat = @"M/d/yy";
        }
        return [formatter stringFromDate:self];
    }
}


- (NSString*)formatDateTime {
    NSTimeInterval diff = abs([self timeIntervalSinceNow]);
    if (diff < TT_DAY) {
        return [self formatTime];
        
    } else if (diff < TT_5_DAYS) {
        static NSDateFormatter* formatter = nil;
        if (nil == formatter) {
            formatter = [[NSDateFormatter alloc] init];
            formatter.dateFormat = @"EEE h:mm a";
        }
        return [formatter stringFromDate:self];
        
    } else {
        static NSDateFormatter* formatter = nil;
        if (nil == formatter) {
            formatter = [[NSDateFormatter alloc] init];
            formatter.dateFormat = @"MMM d h:mm a";
        }
        return [formatter stringFromDate:self];
    }
}


- (NSString*)formatRelativeTime {
    NSTimeInterval elapsed = [self timeIntervalSinceNow];
    if (elapsed > 0) {
        if (elapsed <= 1) {
            return @"in just a moment";
        }
        else if (elapsed < TT_MINUTE) {
            int seconds = (int)(elapsed);
            return [NSString stringWithFormat:@"in %d seconds", seconds];
            
        }
        else if (elapsed < 2*TT_MINUTE) {
            return @"in about a minute";
        }
        else if (elapsed < TT_HOUR) {
            int mins = (int)(elapsed/TT_MINUTE);
            return [NSString stringWithFormat:@"in %d minutes", mins];
        }
        else if (elapsed < TT_HOUR*1.5) {
            return @"in about an hour";
        }
        else if (elapsed < TT_DAY) {
            int hours = (int)((elapsed+TT_HOUR/2)/TT_HOUR);
            return [NSString stringWithFormat:@"in %d hours", hours];
        }
        else {
            return [self formatDateTime];
        }
    }
    else {
        elapsed = -elapsed;
        
        if (elapsed <= 1) {
            return @"just a moment ago";
            
        } else if (elapsed < TT_MINUTE) {
            int seconds = (int)(elapsed);
            return [NSString stringWithFormat:@"%d seconds ago", seconds];
            
        } else if (elapsed < 2*TT_MINUTE) {
            return @"about a minute ago";
            
        } else if (elapsed < TT_HOUR) {
            int mins = (int)(elapsed/TT_MINUTE);
            return [NSString stringWithFormat:@"%d minutes ago", mins];
            
        } else if (elapsed < TT_HOUR*1.5) {
            return @"about an hour ago";
            
        } else if (elapsed < TT_DAY) {
            int hours = (int)((elapsed+TT_HOUR/2)/TT_HOUR);
            return [NSString stringWithFormat:@"%d hours ago", hours];
            
        } else {
            return [self formatDateTime];
        }
    }
}


- (NSString*)formatShortRelativeTime {
    NSTimeInterval elapsed = abs([self timeIntervalSinceNow]);
    
    if (elapsed < TT_MINUTE) {
        return @"<1m";
        
    } else if (elapsed < TT_HOUR) {
        int mins = (int)(elapsed / TT_MINUTE);
        return [NSString stringWithFormat:@"%dm", mins];
        
    } else if (elapsed < TT_DAY) {
        int hours = (int)((elapsed + TT_HOUR / 2) / TT_HOUR);
        return [NSString stringWithFormat:@"%dh", hours];
        
    } else if (elapsed < TT_WEEK) {
        int day = (int)((elapsed + TT_DAY / 2) / TT_DAY);
        return [NSString stringWithFormat:@"%dd", day];
        
    } else {
        return [self formatShortTime];
    }
}


- (NSString*)formatDay:(NSDateComponents*)today yesterday:(NSDateComponents*)yesterday {
    static NSDateFormatter* formatter = nil;
    if (nil == formatter) {
        formatter = [[NSDateFormatter alloc] init];
        formatter.dateFormat = @"MMMM d";
    }
    
    NSCalendar* cal = [NSCalendar currentCalendar];
    NSDateComponents* day = [cal components:NSDayCalendarUnit|NSMonthCalendarUnit|NSYearCalendarUnit
                                   fromDate:self];
    
    if (day.day == today.day && day.month == today.month && day.year == today.year) {
        return @"Today";
        
    } else if (day.day == yesterday.day && day.month == yesterday.month
               && day.year == yesterday.year) {
        return @"Yesterday";
        
    } else {
        return [formatter stringFromDate:self];
    }
}


- (NSString*)formatMonth {
    static NSDateFormatter* formatter = nil;
    if (nil == formatter) {
        formatter = [[NSDateFormatter alloc] init];
        formatter.dateFormat = @"MMMM";
    }
    return [formatter stringFromDate:self];
}


- (NSString*)formatYear {
    static NSDateFormatter* formatter = nil;
    if (nil == formatter) {
        formatter = [[NSDateFormatter alloc] init];
        formatter.dateFormat = @"yyyy";
    }
    return [formatter stringFromDate:self];
}

- (NSString *)timeStamp {
    static NSDateFormatter* formatter = nil;
    if (nil == formatter) {
        formatter = [[NSDateFormatter alloc] init];
        formatter.dateFormat = @"yyyyMMddHHmmss";
    }
    return [formatter stringFromDate:self];
}


@end
