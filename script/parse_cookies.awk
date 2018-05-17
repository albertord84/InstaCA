
/^\s*$/ { next; }

BEGIN {
    printf "["
}

{
    if (c>0) {
        printf ",";
    }
    
    if (!($0 ~ /\#/)) {
        printf "{\"domain\":\""$1"\",\"path\":\""$3"\",\"expire\":\""$5"\",\"name\":\""$6"\",\"value\":\""$7"\"}";
        c++;
    }

}

END {
    printf "]"
}