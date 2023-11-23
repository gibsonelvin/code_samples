# Design a simple movie recommendation system for a social media platform.
# Suggest a list of movies to a user based on the frequency it has been watched by his/her social network.
# Assume the Helper functions are implemented for you.

public List<User> getFriends(User user);
public List<Movie> getMoviesWatched(User user);
Movie {
    name,
    id,
}

User {
    
}


sub getRecommendedMovies {
    my %watches;
    my $friends_degree = shift;
    my $current_depth = shift || 0;
    
    my $user = shift;
    my $current_depth = 0;
    
    $friends = getFriends($user);
    for my $friend (@$friends) {
        my $movies = getMoviesWatchedBy($friend, $current_depth, $friend_degree);
        
        # Adds appropriate movie count
        for my $movie (@$movies) {
            if(!defined %watches{$movie->id}) {
                $watches{$movie->id} = {
                    cnt => 1,
                    name => $movie->name
                };
                    
            } else {
                $watches{$movie->id}->{'cnt'}++;
            }
        }
        
        $current_depth--;
        
        
    }
    return sort {$a->{'cnt'} <=> $b->{'cnt'}} %watches;
   
}

sub getMoviesWatchedBy {
    my ($friend, $depth, $degree) = @_;
    $depth++;
    
    $movies = getMoviesWatched($friend);
    if($degree > $depth) {
        $other_friends = getFriends($friend);
        for my $other_friend (@$other_friends) {
            $movies = array_merge($movies, getMoviesWatchedBy($other_friend, $depth, $degree));
        }
    }
    
    $depth--;
    return $movies;
}
    
